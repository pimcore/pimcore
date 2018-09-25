# Pimcore Workflow Management Migration

With Pimcore 5.5 Pimcore Workflow Management was refactored to use Symfony workflow component. Old workflows are not 
supported anymore, old workflows will not work anymore and need to be migrated to the new Pimcore Workflow Management. 

For details about features and configuration options see [Workflow Management Docs](../../../07_Workflow_Management/README.md). 

## Migration of Configuration 
Old php configuration files are not deleted by Pimcore, they are just ignored. New configuration takes place in Symfony 
configuration tree in `pimcore` namespace.

##### Conceptual changes

| Concept before Pimcore 5.5 | Concept in Pimcore 5.5 | 
|----------------------------------|-----------------------------------------|
| state                 | There is no matching concept for states. Use places instead, config option for colors also moved to places.  | 
| status                | Use places for defining the workflow steps.  |
| actions               | Use transitions for defining actions and place transitions. | 
| transitionDefinitions | Is part of the transition definition now. | 

##### Here is an overview of what can be migrated how: 

| Configuration before Pimcore 5.5 | Configuration in Pimcore 5.5 | 
|----------------------------------|-----------------------------------------|
| `'name' => 'A friendly name'`    | Use `label` in workflow definition for a friendly name. |
| `'id' => 1,`                     | There is no numerical ID anymore. The key of the workflow definition defines the unique workflow name. |
| `'workflowSubject' => ... `      | Use `supports` for simple definitions and `supports_strategy` for more advanced subject criteria. |
| `'defaultStatus' => 'todo'`      | Use `initial_place` in workflow definition. |
| `'allowUnpublished'=> true,`     | Use `supports_strategy` for more advanced subject criteria. | 
| `'states' => [ ...`              | No matching concept, use places instead. Color config also moved to places. | 
| `'statuses' => [ ...`            | Use `places` now.  |
| `'statuses' => [[ 'elementPublished'` | Use `changePublishedState` option in `transition` definition.  |  
| `'actions' => [ ...`             | Use `transitions` now. |
| `'actions' => [[ 'name' => 'start_progress' ` | Unique name defined by the key of a `transition`. |
| `'actions' => [[ 'label' => 'Start Progress' ` | Use `label` option in `options` array of `transition` definition. | 
| `'actions' => [[ 'transitionTo' => [ ... ] ` | Use `to` option of `transition` definition. |
| `'actions' => [[ 'notes' => [ ... ] ` | Use `notes` option in `options` array of `transition` definition. |
| `'actions' => [[ 'additionalFields' => [ ... ] ` | Use `notes.additionalFields` option in `options` array of `transition` definition. | 
| `'actions' => [[ 'users' => [ ... ] ` | Use `guard` option in `transition` definition. There advanced definitions of who and when an transition is allowed can be configured. | 
| `'actions' => [[ 'events' => [ ... ] ` | See events section later. | 
| `'actions' => [[ 'notificationUsers' => [ ... ] ` | Use `notifyUsers` and `notifyRoles` option in `options.notificationSettings` array of `transition` definition. **Important:** Now names are used instead of IDs. |
| `'transitionDefinitions' => [ ... ` | Use `from` option of `transition` definition. |
| `'transitionDefinitions' => [[ 'globalActions' => [ ... ]  ` | Use `globalActions` in workflow definition. |

##### Events

| Event before Pimcore 5.5 | Event in Pimcore 5.5 | 
|----------------------------------|-----------------------------------------|
|`pimcore.workflowmanagement.preAction` | Use `workflow.leave` (see [Symfony Workflow Events](https://symfony.com/doc/3.4/workflow/usage.html#using-events) for details) or `pimcore.workflow.preGlobalAction` for global actions. |
|`pimcore.workflowmanagement.postAction` | Use `workflow.completed` (see [Symfony Workflow Events](https://symfony.com/doc/3.4/workflow/usage.html#using-events) for details) or `pimcore.workflow.postGlobalAction` for global actions. |
|`pimcore.workflowmanagement.preReturnAvailableActions` | Use `workflow.[workflow name].guard.[transition name]` (see [Symfony Guard Events](https://symfony.com/doc/3.4/workflow/usage.html#guard-events) for details). |
|`pimcore.workflowmanagement.action.before` | Use `workflow.[workflow name].leave.[place name]` (see [Symfony Workflow Events](https://symfony.com/doc/3.4/workflow/usage.html#using-events) for details). |
|`pimcore.workflowmanagement.action.success` | Use `workflow.[workflow name].completed.[transition name]` (see [Symfony Workflow Events](https://symfony.com/doc/3.4/workflow/usage.html#using-events) for details). |
|`pimcore.workflowmanagement.action.failure` | Symfony workflow module has no corresponding event as exceptions are thrown to outside world. |


## Migration of Workflow Status Information
Besides the configuration, also the status information for existing elements needs to be migrated. Since the migration 
task is depending on the new configuration, it cannot be done automatically. 

Here is what Pimcore provides and what steps need to be done manually: 

- The Pimcore migration adds additional columns to the `element_workflow_state` table
  - `place`: name of the place an element is currently in
  - `workflow`: name of the workflow
- Following columns of the `element_workflow_state` table are deprecated now, but Pimcore DOES NOT remove them (for data migration purposes)
  - `workflowId`
  - `state`
  - `status`
- For migrating status information, 
  - all data in `workflowId` needs to be migrated to `workflow` - convert from workflow id to a workflow name
  - manually delete `workflowId` column, or set value to `0` for every record. 
  - all data in `status` need to be migrated to `place` - ideally all places in the new configuration are called the same 
    as the status were in the old configuration, then it is just a copy from one to the other column.   
    

## Additional System Requirements
To render the graph in workflow overview, `graphviz` is needed as additional system requirement. 