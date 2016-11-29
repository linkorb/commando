<?php

namespace Commando\Model;

class CommandArgument
{
    protected $name;
    protected $default = null;
    protected $required = true;
    
    
    public function __construct($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getDefault()
    {
        return $this->default;
    }
    
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }
    
    public function getRequired()
    {
        return $this->required;
    }
    
    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }
}
