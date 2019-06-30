<?php

namespace Commando\Model;

use Commando\Model\CommandArgument;

class Command
{
    protected $name;
    protected $inputs = [];
    protected $template;
    protected $timeout = 60*45;
    
    public function __construct($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getTemplate()
    {
        return $this->template;
    }
    
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }
    
    public function getInputs(): array
    {
        return $this->inputs;
    }
    
    public function addInput(CommandInput $input)
    {
        $this->inputs[$input->getName()] = $input;
        return $this;
    }
    
    public function getTimeout()
    {
        return $this->timeout;
    }
    
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }
    
}
