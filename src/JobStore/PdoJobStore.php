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
        if (isset($options['tablename'])) {
            $this->tablename = $options['tablename'];
        }
        if (isset($options['job_tablename'])) {
            $this->tablename = $options['job_tablename'];
        }
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
    
    public function popJob()
    {
        
        // safeguard against already processing or errored jobs?
        $sql = sprintf(
            "SELECT * FROM %s WHERE status!='NEW' AND status!='SUCCESS' AND status!='SKIPPED' ",
            $this->tablename
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            throw new RuntimeException("Jobs in unknown status: " . $row['status']);
        }
        
        $sql = sprintf(
            "SELECT * FROM %s WHERE status='NEW'",
            $this->tablename
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute([]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return;
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
        $arguments = json_decode($row['arguments'], true);
        $job->setArguments($arguments);
        return $job;
    }

    public function completeJob(Job $job)
    {
        print_r($job);
        
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
        
        $status = 'SUCCESS';
        if ($job->getExitCode()!=0) {
            $status = 'FAILURE';
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
        
        return true;
    }
}
