<?php

namespace Commando\Model;

class Job
{
    protected $id;
    protected $commandName;
    protected $inputs = [];
    
    protected $startStamp;
    protected $endStamp;
    
    protected $exitCode;
    
    protected $stdout;
    protected $stderr;
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    public function getCommandName()
    {
        return $this->commandName;
    }
    
    public function setCommandName($commandName)
    {
        $this->commandName = $commandName;
        return $this;
    }
    
    public function getInputs()
    {
        return $this->inputs;
    }
    
    public function setInput(string $name, $value)
    {
        $this->inputs[$name] = $value;
        return $this;
    }
    
    public function getStartStamp()
    {
        return $this->startStamp;
    }
    
    public function setStartStamp($startStamp)
    {
        $this->startStamp = $startStamp;
        return $this;
    }
    
    public function getEndStamp()
    {
        return $this->endStamp;
    }
    
    public function setEndStamp($endStamp)
    {
        $this->endStamp = $endStamp;
        return $this;
    }
    
    public function getDuration()
    {
        if (!$this->startStamp) {
            return -1;
        }
        if (!$this->endStamp) {
            return time() - (int)$this->startStamp;
        }
        return $this->endStamp - $this->startStamp;
    }
    
    public function getExitCode()
    {
        return $this->exitCode;
    }
    
    public function setExitCode($exitCode)
    {
        $this->exitCode = $exitCode;
        return $this;
    }
    
    public function getStdout()
    {
        return $this->stdout;
    }
    
    public function setStdout($stdout)
    {
        $this->stdout = $stdout;
        return $this;
    }
    
    public function getStderr()
    {
        return $this->stderr;
    }
    
    public function setStderr($stderr)
    {
        $this->stderr = $stderr;
        return $this;
    }
}
