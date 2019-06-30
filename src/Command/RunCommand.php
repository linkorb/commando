<?php

namespace Commando\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Commando\CommandoFactory;

class RunCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Run commando')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_OPTIONAL,
                'Config file to use'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configFilename = $input->getOption('config');
        $commando = CommandoFactory::create($configFilename);
        
        $output->write("Commando: Run (config = $configFilename)\n");
        $commando->run();
    }
}
