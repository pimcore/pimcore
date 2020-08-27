# Working with PHP API

The Pimcore workflow management can also be used via PHP API. 

## Using Workflow API to modify Places of Elements

Since the Pimcore workflow management is based on the Symfony workflow component, also its API can be 
used the same way. For details see [Symfony docs](https://symfony.com/doc/3.4/workflow/usage.html).

Additionally Pimcore provides the `\Pimcore\Workflow\Manager`, which provides additional functionality for working
with the workflow management - like additional methods for apply places with additional data (`applyWithAdditionalData()`)
and apply global actions (`applyGlobalAction()`). 

See following example how to interact with workflows with PHP API. 


```php

/**
 * $object ... your element, e.g. a Pimcore data object
 * $workflowRegistry Symfony\Component\Workflow\Registry from Symfony container
 */
 
$workflow = $workflowRegistry->get($object, 'workflow');

if($workflow->can($object, 'content_ready')) {

    //modify workflow via Symfony APi and without saving additional data
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

Symfony workflow module comes with a bunch of events that can be used for customizing and extending 
default workflow functionality. See [Symfony docs](https://symfony.com/doc/3.4/workflow/usage.html#using-events)
for details. 

In addition to the Symfony events, Pimcore provides two additional events for global actions: 
- `pimcore.workflow.preGlobalAction`
- `pimcore.workflow.postGlobalAction`
See [WorkflowEvents](https://github.com/pimcore/pimcore/blob/master/lib/Event/WorkflowEvents.php) for details. 

