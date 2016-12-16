<?php

namespace Commando\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\GenericEvent;
use HipChat\HipChat;

class HipChatSubscriber implements EventSubscriberInterface
{
    protected $hipChat;
    protected $roomId;
    protected $mentions;
    protected $name;
    
    public function __construct(HipChat $hipChat, $roomId, $mentions, $name = 'Commando')
    {
        $this->hipChat = $hipChat;
        $this->roomId = $roomId;
        $this->mentions = $mentions;
        $this->name = $name;
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
        $this->hipChat->message_room(
            $this->roomId,
            $this->name,
            trim($this->mentions . ' Error: ') . $event['message'],
            true,
            HipChat::COLOR_RED,
            HipChat::FORMAT_TEXT
        );
    }
    
    
    public function onRun(GenericEvent $event)
    {
        $this->hipChat->message_room(
            $this->roomId,
            $this->name,
            'Starting',
            true,
            HipChat::COLOR_YELLOW,
            HipChat::FORMAT_TEXT
        );
    }
    
    
    
    public function onJobStart(GenericEvent $event)
    {
        $job = $event->getSubject();
        $this->hipChat->message_room(
            $this->roomId,
            $this->name,
            'Started job #' . $job->getId(),
            true,
            HipChat::COLOR_YELLOW,
            HipChat::FORMAT_TEXT
        );
    }
    
    public function onJobSuccess(GenericEvent $event)
    {
        $job = $event->getSubject();
        $this->hipChat->message_room(
            $this->roomId,
            $this->name,
            'Success job: ' . $job->getId(),
            true,
            HipChat::COLOR_GREEN,
            HipChat::FORMAT_TEXT
        );
    }
    
    public function onJobError(GenericEvent $event)
    {
        $job = $event->getSubject();
        $this->hipChat->message_room(
            $this->roomId,
            $this->name,
            $this->mentions . ' ERROR job: ' . $job->getId(),
            true,
            HipChat::COLOR_RED,
            HipChat::FORMAT_TEXT
        );
    }
    
    public function onJobException(GenericEvent $event)
    {
        $job = $event->getSubject();
        $this->hipChat->message_room(
            $this->roomId,
            $this->name,
            $this->mentions . ' Exception on job #' . $job->getId(),
            true,
            HipChat::COLOR_RED,
            HipChat::FORMAT_TEXT
        );
    }
}
