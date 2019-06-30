<?php

namespace Commando\JobStore;

use Commando\Model\Job;

interface JobStoreInterface
{
    public function popJob(): ?Job;
    public function updateJob(Job $job): void;
}
