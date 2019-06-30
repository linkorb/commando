<?php

namespace Commando\ConfigLoader;

use Commando\Commando;
use Commando\Model\Command;
use Commando\Model\CommandArgument;

abstract class ArrayConfigLoader
{
    public function load(array $config)
    {
        $commando = new Commando();
        if (isset($config['store'])) {
            $storeClass = "Commando\\JobStore\\" . $config['store']['type'] . "JobStore";
            $store = new $storeClass($config['store']['options'] ?? []);
            $commando->setJobStore($store);
        }
        
        foreach ($config['commands'] as $commandData) {
            if (!isset($commandData['name'])) {
                throw new RuntimeException("Missing required name for command");
            }
            $name = $commandData['name'];
            $command = new Command($name);
            if (!isset($commandData['template'])) {
                throw new RuntimeException("Missing required template for command `" . $name . "`");
            }
            $command->setTemplate($commandData['template']);
            if (isset($commandData['timeout'])) {
                $command->setTimeout($commandData['timeout']);
            }
            
            $arguments = [];
            if (isset($commandData['arguments'])) {
                foreach ($commandData['arguments'] as $argumentData) {
                    $argument = new CommandArgument($argumentData['name']);
                    if (isset($argumentData['default'])) {
                        $argument->setDefault($argumentData['default']);
                    }
                    if (isset($argumentData['required'])) {
                        $argument->setRequired($argumentData['required']);
                    }
                    $command->addArgument($argument);
                }
            }
            $commando->addCommand($command);
        }
        //print_r($commando);
        return $commando;
    }
}
