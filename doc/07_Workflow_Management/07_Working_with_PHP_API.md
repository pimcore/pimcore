---
title: Working with PHP API
description: Apply transitions, pass additional data, and listen to workflow events programmatically.
---

# Working with PHP API

Interact with workflows programmatically through the Symfony workflow component and Pimcore's `Workflow\Manager`.

## Using the Workflow API to Modify Places of Elements

Since the Pimcore workflow management builds on the Symfony workflow component,
use the Symfony API directly.
See [Symfony docs](https://symfony.com/doc/current/workflow/usage.html) for details.

Additionally, Pimcore provides `\Pimcore\Workflow\Manager` with methods for applying places
with additional data (`applyWithAdditionalData()`) and applying global actions (`applyGlobalAction()`).

The following example shows how to interact with workflows through the PHP API.

```php

/**
 * $object ... your element, e.g. a Pimcore data object
 * $workflowRegistry Symfony\Component\Workflow\Registry from Symfony container
 */

$workflow = $workflowRegistry->get($object, 'workflow');

if($workflow->can($object, 'content_ready')) {

    //modify workflow via Symfony API and without saving additional data
    $workflow->apply($object, 'content_ready');

    //make sure you save the workflow subject afterwards if any data was changed during transition
    //e.g. by a marking store
    $object->save();

}

if($workflow->can($object, 'publish')) {

    //modify workflow with Pimcore Workflow Manager - notes are written with additional data
    $additionalData = [
        NotesSubscriber::ADDITIONAL_DATA_NOTES_COMMENT => 'this is some additional note',
        NotesSubscriber::ADDITIONAL_DATA_NOTES_ADDITIONAL_FIELDS => [
            'timeWorked' => 20
        ]
    ];

    /**
     * $workflowManager Pimcore\Workflow\Manager from Symfony container
     */

    //last parameter defines if workflow subject should be saved after transition
    $workflowManager->applyWithAdditionalData($workflow, $object, 'publish', $additionalData, true);

}
```


## Using Events for Additional Functionality

The Symfony workflow module provides events for customizing and extending default workflow functionality.
See [Symfony docs](https://symfony.com/doc/current/workflow/usage.html#using-events) for details.

In addition to the Symfony events, Pimcore provides two events for global actions:
- `pimcore.workflow.preGlobalAction`
- `pimcore.workflow.postGlobalAction`

See [WorkflowEvents](https://github.com/pimcore/pimcore/blob/2026.x/lib/Event/WorkflowEvents.php) for details.

### Using Additional Data in Events

When additional data fields are defined in the transition configuration,
retrieve that data in event listener functions.

Define an additional field in the workflow configuration:

```yaml
transitions:
    close_product:
        from: open
        to: closed
        options:
            label: close product
            notes:
                commentEnabled: 1
                commentRequired: 1
                additionalFields:
                    -
                        name: mySelect
                        title: please select a type
                        fieldType: select
                        fieldTypeSettings:
                            options:
                                -
                                    key: Option A
                                    value: a
                                -
                                    key: Option B
                                    value: b
                                -
                                    key: Option C
                                    value: c
```

Register the transition event in `services.yaml`:

```yaml
services:
    App\EventListener\WorkflowsEventListener:
        tags:
            - { name: kernel.event_listener, event: workflow.projectWorkflow.transition.close_product, method: onCloseProduct }
```

The additional data is then available in the transition event:

```php
<?php

namespace App\EventListener;

use Symfony\Component\Workflow\Event\TransitionEvent;

class WorkflowsEventListener
{
    public function onCloseProduct(TransitionEvent $event): void
    {
        $context = $event->getContext();
        $additionalData = $context["additional"];

        $mySelectValue = $additionalData["mySelect"];

        // do something with the value
    }
}
```
