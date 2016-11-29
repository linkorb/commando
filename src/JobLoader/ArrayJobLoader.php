<?php

namespace Commando\JobLoader;

use Commando\Model\Job;

abstract class ArrayJobLoader
{
    public function load($data)
    {
        $job = new Job();
        $job->setCommandName($data['command']);
        $job->setArguments($data['arguments']);
        return $job;
    }
}
