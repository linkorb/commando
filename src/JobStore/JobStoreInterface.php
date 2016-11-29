<?php

namespace Commando\JobStore;

interface JobStoreInterface
{
    public function popJob();
}
