<?php

namespace Commando\ConfigLoader;

use Commando\Commando;
use Commando\Model\Command;
use Commando\Model\CommandInput;
use RuntimeException;

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
        
        foreach ($config['commands'] as $commandName => $commandData) {
            if (isset($commandData['name'])) {
                $commandName = $commandData['name'];
            }
            $command = new Command($commandName);
            if (!isset($commandData['template'])) {
                throw new RuntimeException("Missing required template for command `" . $name . "`");
            }
            $command->setTemplate($commandData['template']);
            if (isset($commandData['timeout'])) {
                $command->setTimeout($commandData['timeout']);
            }
            $inputsConfig = $commandData['inputs'] ?? $commandData['arguments'] ?? [];

            foreach ($inputsConfig as $inputName => $inputData) {
                if (isset($inputData['name'])) {
                    $inputName = $inputData['name'];
                }
                $input = new CommandInput($inputName);
                if (isset($inputData['default'])) {
                    $input->setDefault($inputData['default']);
                }
                if (isset($inputData['required'])) {
                    $inputData->setRequired($inputData['required']);
                }
                $command->addInput($input);
            }
            $commando->addCommand($command);
        }
        //print_r($commando);
        return $commando;
    }
}
