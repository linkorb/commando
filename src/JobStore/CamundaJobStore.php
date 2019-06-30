<?php

namespace Commando\JobStore;

use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;
use Commando\Model\Job;
use RuntimeException;

class CamundaJobStore implements JobStoreInterface
{
    protected $httpFactory;
    protected $httpClient;
    protected $username;
    protected $password;
    protected $workerId;

    public function __construct(array $options)
    {
        $this->httpFactory = new Psr17Factory();
        $this->httpClient = new Psr18Client();
        $this->baseUrl = $options['baseUrl'] ?? null;
        $this->username = $options['username'] ?? null;
        $this->password = $options['password'] ?? null;
        $this->workerId = $options['workerId'] ?? 'commando-' . time();
        $this->topics = [
            [
                'topicName' => 'message:send',
                'lockDuration' => 10*1000,
            ]
        ];

        if (!$this->baseUrl) {
            throw new RuntimeException("No Camunda baseUrl provided");
        }
    }
    
    public function popJob(): ?Job
    {
        $body = [
            'workerId' => $this->workerId,
            'maxTasks' => 1,
            'usePriority' => true,
            'topics' => $this->topics
        ];

        $rows = $this->request('POST', '/external-task/fetchAndLock', $body);

        if (count($rows)>0) {
            foreach ($rows as $row) {
                $job = new Job();
                $job->setId($row['id']);
                $job->setCommandName($row['topicName']);
                $inputs = [];
                foreach ($row['variables'] as $name => $v) {
                    $job->setInput($name, $v['value'] ?? null);
                }

                // print_r($job);
                
                return $job;
            }
        }

        // returning without job
        return null;
    }

    public function updateJob(Job $job): void
    {
        if ($job->getExitCode() === null) {
            // Job not yet finished
            return;
        }

        if ($job->getExitCode()===0) {
            // success
            $body = [
                'workerId' => $this->workerId,
                'variables' => [
                    'awesome' => [
                        'value' => 'sauce',
                    ]
                ]
            ];
            // echo "Reporting complete\n";
            $res = $this->request('POST', '/external-task/' . $job->getId() . '/complete', $body);
        } else {
            $body = [
                'workerId' => $this->workerId,
                'errorMessage' => 'Commando failed to execute',
                'retries' => 0,
                'errorDetails' => $job->getStdErr(),
                'retryTimeout' => 10*1000,
            ];
            // echo "Reporting failure\n";
            $res = $this->request('POST', '/external-task/' . $job->getId() . '/failure', $body);
        }
        return;
    }

    private function request(string $method, string $url, array $data = []): ?array
    {
        if ($url[0]=='/') {
            $url = $this->baseUrl . $url;
        }
        $request = $this->httpFactory->createRequest($method, $url);

        $bodyStream = $this->httpFactory->createStream(
            json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
        );
        $request = $request->withBody($bodyStream);
        $request = $request->withHeader(
            'Content-type', 'application/json'
        );

        if ($this->username && $this->password) {
            $request = $request->withHeader(
                'Authorization',
                'Basic ' . base64_encode($this->username.':'.$this->password)
            );
        }
        $response = $this->httpClient->sendRequest($request);
        
        // is the HTTP status code 2xx ?
        if (($response->getStatusCode()<200) || ($response->getStatusCode()>=300)) {
            print_r($response);
            switch ($response->getStatusCode()) {
                case 401:
                    throw new \RuntimeException('Unauthorized. Configure credentials if auth is enabled on this server.');
                default: 
                    throw new \RuntimeException('Unexpected HTTP status code: ' . $response->getStatusCode());
            }
        }
        $json = (string)$response->getBody();
        $data = json_decode($json, true);
        return $data;
    }


}