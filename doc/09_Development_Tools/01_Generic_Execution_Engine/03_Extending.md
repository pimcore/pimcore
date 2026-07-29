---
title: Extending the Execution Engine
description: React to job run state changes via events.
---

# Extending the Generic Execution Engine

## Events

The following event extends the Generic Execution Engine:

* `Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent`

This event fires whenever a job run's state changes.
The event object contains the job run ID, the previous state, and the new state.

### Example

Send an email notification when a job run fails:

```php
<?php

namespace App\EventListener;

use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Mail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SendEmailOnFailedState implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            JobRunStateChangedEvent::class  => 'onFailedState',
        ];
    }

    public function onFailedState(JobRunStateChangedEvent $event): void
    {
        $state = $event->getNewState();
        if ($state !== 'failed') {
            return;
        }

        // Notify user about failed job run
        $mail = new Mail();
        $mail->addTo('user@lorem.com', 'User');
        $mail->setSubject('Job Run ' .$event->getJobRunId() . ' failed');
        $mail->setBody('The job run ' . $event->getJobRunId() . ' failed. Please check the job run log for more information.');
        $mail->send();
    }
}

```
