<?php

namespace Commando;

use Commando\JobStore\JobStoreInterface;
use Commando\Model\Command;
use Commando\Model\Job;
use RuntimeException;
use LightnCandy\LightnCandy;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use PidHelper\PidHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

class Commando
{
    protected $jobStore;
    protected $commands = [];
    protected $dispatcher;
    
    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
    }
    
    public function getDispatcher()
    {
        return $this->dispatcher;
    }
    
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
        
        $pidHelper = new PidHelper('/tmp/', 'commando.pid');
        if (!$pidHelper->lock()) {
            exit("Commando is already running\n");
        }

        $event = new GenericEvent(null, []);
        $this->getDispatcher()->dispatch('commando.run', $event);


        if (!$this->jobStore) {
            throw new RuntimeException("Commando JobStore not configured");
        }
        
        $running = true;
        while ($running) {
            $job = null;
            try {
                //echo "Popping a Job:\n";
                $job = $this->jobStore->popJob();
            } catch (\Exception $e) {
                $event = new GenericEvent(null, ['message' => $e->getMessage()]);
                $this->getDispatcher()->dispatch('commando.error', $event);

                echo $e->getMessage() . "\n";
                sleep(20);
            }
            
            if (!$job) {
                sleep(2);
            } else {
                try {
                    echo "Starting job " . $job->getId() . " " . $job->getCommandName() . "\n";
                    $event = new GenericEvent($job, []);
                    $this->getDispatcher()->dispatch('job.start', $event);
                    
                    $this->runJob($job);
                    if ($job->getExitCode()==0) {
                        echo "Success: job " . $job->getId() . "\n";
                        $event = new GenericEvent($job, []);
                        $this->getDispatcher()->dispatch('job.success', $event);
                    } else {
                        echo "Error: job " . $job->getId() . "\n";
                        $event = new GenericEvent($job, []);
                        $this->getDispatcher()->dispatch('job.error', $event);
                    }
                } catch (\Exception $e) {
                    $event = new GenericEvent($job, ['message' => $e->getMessage()]);
                    $this->getDispatcher()->dispatch('job.exception', $event);
                    
                    echo $e->getMessage() . "\n";
                    sleep(5);
                }
            }
            // loop!
        }
        $pidHelper->unlock();
        return;
    }
    
    public function runJob(Job $job)
    {
        echo "Running job" . PHP_EOL;
        print_r($job);
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
        $line = $renderer($job->getInputs());
        
        //echo "TEMPLATE: $template\n";
        //echo "RUNNING: $line\n";
        
        $process = new Process($line);
        $process->setTimeout($command->getTimeout());
        $process->setIdleTimeout($command->getTimeout());
        $job->setStderr(null);
        $job->setStdout(null);
        $job->setExitCode(null);
        $this->jobStore->updateJob($job);

        $job->setStartStamp(time());
        $that = $this;
        $process->run(function ($type, $buffer) use ($job, $process, $that) {
            $lines = explode("\n", $buffer);
            foreach ($lines as $line) {
                if ($line && (trim($line, " \n\r\t"))) {
                    $buffer = "[" . date('d/M/Y H:i:s') . " " . str_pad($job->getDuration(), 4, ' ', STR_PAD_LEFT) . "s] " . $line . "\n";
                    if (Process::ERR === $type) {
                        echo 'ERR > ' . $buffer;
                        $job->setStderr($job->getStderr() . $buffer);
                    } else {
                        echo 'OUT > ' . $buffer;
                        $job->setStdout($job->getStdout() . $buffer);
                    }
                }
            }
            $that->jobStore->updateJob($job);
        });
        
        $job->setEndStamp(time());
        $job->setExitCode($process->getExitCode());

        echo "Updating job" . PHP_EOL;
        print_r($job);

        $this->jobStore->updateJob($job);
    }
}
