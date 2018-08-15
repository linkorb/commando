<?php

namespace Commando\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\GenericEvent;
use HipChat\HipChat;

class SlackSubscriber implements EventSubscriberInterface
{
    protected $slack;
    protected $mentions;

    public function __construct(\Maknz\Slack\Client $slack, $mentions)
    {
        $this->slack = $slack;
        $this->mentions = $mentions;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'commando.run' => 'onRun',
            'commando.error' => 'onError',
            'job.start' => 'onJobStart',
            'job.exception' => 'onJobException',
            'job.success' => 'onJobSuccess',
            'job.error' => 'onJobError',
        );
    }

    public function onError(GenericEvent $event)
    {
        $this->slack->send(
            trim($this->mentions . ' Error: ') . $event['message']
        );
    }


    public function onRun(GenericEvent $event)
    {
        $this->slack->send(
            'Starting'
        );
    }



    public function onJobStart(GenericEvent $event)
    {
        $job = $event->getSubject();
        $this->slack->send(
            'Started job #' . $job->getId()
        );
    }

    public function onJobSuccess(GenericEvent $event)
    {
        $job = $event->getSubject();
        $this->slack->send(
            'Success job: ' . $job->getId()
        );
    }

    public function onJobError(GenericEvent $event)
    {
        $job = $event->getSubject();
        $this->slack->send(
            $this->mentions . ' ERROR job: ' . $job->getId()
        );
    }

    public function onJobException(GenericEvent $event)
    {
        $job = $event->getSubject();
        $this->slack->send(
            $this->mentions . ' Exception on job #' . $job->getId()
        );
    }
}
