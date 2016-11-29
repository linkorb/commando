<?php

namespace Commando\JobStore;

use Commando\JobLoader\JsonJobLoader;
use Commando\Model\Job;
use PDO;
use RuntimeException;

class JsonDirJobStore implements JobStoreInterface
{
    protected $path;
    protected $taskLoader;
    
    public function __construct($options)
    {
        $this->path = $options['path'];
        if (!file_exists($this->path)) {
            throw new RuntimeException("JsonDir does not exist: " . $this->path);
        }
        
        if (!file_exists($this->path . '/new')) {
            mkdir($this->path . '/new');
        }
        if (!file_exists($this->path . '/processing')) {
            mkdir($this->path . '/processing');
        }
        if (!file_exists($this->path . '/success')) {
            mkdir($this->path . '/success');
        }
        if (!file_exists($this->path . '/failure')) {
            mkdir($this->path . '/failure');
        }
        
        $this->jobLoader = new JsonJobLoader();
    }
    
    public function popJob()
    {
        $filenames = glob($this->path . '/new/*.json');
        foreach ($filenames as $filename) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            $id = substr(basename($filename), 0, -strlen($extension)-1);
            $job = $this->jobLoader->loadFile($filename);
            $job->setId($id);
            
            $newname = $this->path . '/processing/' . basename($filename);
            rename($filename, $newname);

            return $job;
        }
        return null;
    }
    
    public function completeJob(Job $job)
    {
        $filename = $this->path . '/processing/' . $job->getId() . '.json';
        if (!file_exists($filename)) {
            throw new RuntimeException("Job ID: " . $job->getId() . ' currently not processing');
        }

        if ($job->getExitCode()==0) {
            $newname = $this->path . '/success/' . $job->getId() . '.json';
        } else {
            $newname = $this->path . '/failure/' . $job->getId() . '.json';
        }
        
        rename($filename, $newname);

        $filename = $newname . '.report';
        $data = [
            'id' => $job->getId(),
            
            'start_stamp' => $job->getStartStamp(),
            'end_stamp' => $job->getEndStamp(),
            'duration' => $job->getDuration(),
            
            'statusCode' => $job->getExitCode(),
            'stdout' => $job->getStdout(),
            'stderr' => $job->getStderr(),
        ];
        file_put_contents($filename, json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
        return true;
    }
}
