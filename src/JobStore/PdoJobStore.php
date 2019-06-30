<?php

namespace Commando\JobStore;

use Commando\Model\Job;
use PDO;
use RuntimeException;

class PdoJobStore implements JobStoreInterface
{
    protected $pdo;
    protected $tablename = null;

    public function __construct($options)
    {
        if (!isset($options['pdo'])) {
            throw new RuntimeException("PDO not configured");
        }
        $url = $options['pdo'];
        $this->tablename = $options['tablename'] ?? null;
        if (!$this->tablename) {
            throw new RuntimeException("Commando Job tablename not configured");
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $user = parse_url($url, PHP_URL_USER);
        $pass = parse_url($url, PHP_URL_PASS);
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $dbname = parse_url($url, PHP_URL_PATH);
        if (!$port) {
            $port = 3306;
        }
        $dsn = sprintf(
            '%s:dbname=%s;host=%s;port=%d',
            $scheme,
            substr($dbname, 1),
            $host,
            $port
        );
        //echo $dsn;exit();
        $this->pdo = new PDO($dsn, $user, $pass);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function popJob(): ?Job
    {
        // switch all PROCESSING jobs to FAILURE
        $sql = sprintf(
            "UPDATE %s SET status='FAILURE' WHERE status='PROCESSING'",
            $this->tablename
        );

        // TODO: update NEW jobs with related FAILURE jobs (by group_key) to SKIPPED
        $statement = $this->pdo->prepare($sql);
        $statement->execute([]);
        // safeguard against already processing or errored jobs?
        $sql = sprintf(
            "SELECT * FROM %s
            WHERE status!='NEW' AND status!='SUCCESS' AND status!='SKIPPED' AND status!='FAILURE'
            ORDER BY priority, id",
            $this->tablename
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            throw new RuntimeException("Jobs in unknown status: " . $row['status']);
        }

        $sql = sprintf(
            "SELECT * FROM %s WHERE status='NEW'
            AND (isnull(scheduled_stamp) OR (scheduled_stamp<:now))
            ORDER BY priority, id",
            $this->tablename
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute(
            [
                'now' => time()+(60*60)
            ]
        );
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }



        // Update job status to PROCESSING
        $sql = sprintf(
            "UPDATE %s SET status='PROCESSING' WHERE id=:id",
            $this->tablename
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['id' => $row['id']]);

        //print_r($row);
        $job = new Job();
        $job->setId($row['id']);
        $job->setCommandName($row['command']);

        $inputs = $row['inputs'] ?? $row['arguments'] ?? [];
        $inputs = json_decode($inputs, true);
        foreach ($inputs as $name=>$value) {
            $job->setInput($name, $value);
        }
        return $job;
    }

    public function updateJob(Job $job): void
    {
        //print_r($job);

        // Update job status to PROCESSING
        $sql = sprintf(
            "UPDATE %s SET
            status=:status,
            start_stamp=:start_stamp,
            end_stamp=:end_stamp,
            duration=:duration,
            exitcode=:exitcode,
            stdout=:stdout,
            stderr=:stderr
            WHERE id=:id",
            $this->tablename
        );

        $statement = $this->pdo->prepare($sql);
        if ($job->getExitCode()===null) {
            $status = 'PROCESSING';
        } else {
            $status = 'SUCCESS';
            if ($job->getExitCode()!=0) {
                $status = 'FAILURE';
            }
        }
        $statement->execute(
            [
                'id' => $job->getId(),
                'status' => $status,
                'start_stamp' => $job->getStartStamp(),
                'end_stamp' => $job->getEndStamp(),
                'duration' => $job->getDuration(),
                'exitcode' => $job->getExitcode(),
                'stdout' => $job->getStdout(),
                'stderr' => $job->getStderr()
            ]
        );
        return;
    }
}
