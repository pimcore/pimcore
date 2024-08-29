# Extending Generic Execution Engine

## Extending Generic Execution Engine via Events

Currently, it is possible to use the following event to extend the Generic Execution Engine:

* `Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent`

With this event it is possible to react to changes in the state of a job run.
The event is dispatched whenever the state of a job run changes. The event object contains the job run ID, the previous state and the new state.

### Example

The following example notifies a user via email when a job run fails.

```php
<?php

namespace AppBundle\EventListener;

use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Mail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SendEmailOnFailedState implements EventSubscriberInterface
{
    
    public static function getSubscribedEvents()
    {
        return [
            JobRunStateChangedEvent::class  => 'onFailedState',
        ];
    }

    public function onFailedState(JobRunStateChangedEvent $event)
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
