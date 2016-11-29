<?php

namespace Commando;

use Commando\JobStore\JobStoreInterface;
use Commando\Model\Command;
use Commando\Model\Job;
use RuntimeException;
use LightnCandy\LightnCandy;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Commando
{
    protected $jobStore;
    protected $commands = [];
    
    public function getJobStore()
    {
        return $this->jobStore;
    }
    
    public function setJobStore(JobStoreInterface $jobStore)
    {
        $this->jobStore = $jobStore;
        return $this;
    }
    
    
    public function addCommand(Command $command)
    {
        $this->commands[$command->getName()] = $command;
    }
    
    public function hasCommand($name)
    {
        return isset($this->commands[$name]);
    }
    
    public function getCommand($name)
    {
        if (!$this->hasCommand($name)) {
            throw new RuntimeException("Undefined command: " . $name);
        }
        return $this->commands[$name];
    }
    
    public function run()
    {
        if (!$this->jobStore) {
            throw new RuntimeException("Commando JobStore not configured");
        }
        $job = $this->jobStore->popJob();
        if (!$job) {
            return;
        }
        $this->runJob($job);
        return;
    }
    
    public function runJob(Job $job)
    {
        $command = $this->getCommand($job->getCommandName());

        $template = $command->getTemplate();
        $php = LightnCandy::compile(
            $template,
            [
                'flags' => LightnCandy::FLAG_RENDER_DEBUG | LightnCandy::FLAG_HANDLEBARSJS
            ]
        );
        $renderer = LightnCandy::prepare($php);
        //TODO: handle undefined/default variables?
        $line = $renderer($job->getArguments());
        
        //echo "TEMPLATE: $template\n";
        //echo "RUNNING: $line\n";
        
        $process = new Process($line);
        $process->setTimeout($command->getTimeout());
        $process->setIdleTimeout($command->getTimeout());

        $job->setStartStamp(time());
        $process->run();
        $job->setEndStamp(time());

        $job->setStdout($process->getOutput());
        $job->setStderr($process->getErrorOutput());
        $job->setExitCode($process->getExitCode());

        $this->jobStore->completeJob($job);
    }
}
