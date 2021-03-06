<?php

namespace Commando\Model;

class CommandInput
{
    protected $name;
    protected $default = null;
    protected $required = true;
    
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    public function getName(): string
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
    
    public function getRequired(): bool
    {
        return $this->required;
    }
    
    public function setRequired(bool $required)
    {
        $this->required = $required;
        return $this;
    }
}
