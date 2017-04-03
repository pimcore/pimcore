# Workflow Management

## General
Workflow Management provides a structured, orchestrated set of steps to maintain and improve data quality held within Pimcore 
elements (assets, documents, objects).

There are several new concepts to know about when using Workflow Management module, each is lightweight and defined within 
a single configuration file, allowing developers to customize Pimcore even further.

### Concepts 

**States**
A high level overview of where information is in the PIM. Create RAG/Traffic light configurations for quickly showing 
the state of data.
* Great for custom reporting and customising availability of information from different perspectives.

**Statuses**
The position an element is at within a workflow. Statuses help define lifecycle specific user permissions.
* control who can view/edit/process information once a product goes Live or an order is paid for.

**Actions**
Tasks or processes that can be performed on elements. Actions define.
* What happens to information before/after that action happens? i.e. Should the product be synced with a 3rd party system, 
or an email sent to a customer.
* Who can perform an action – i.e. only the manager’s role can approve a product smt or ship an order.
* Which additional information is necessary to complete the action.

**Transition Definitions**
These hold together States, Status & Actions into a complete workflow configuration.
* An order is *"paid"* therefore it can "shipped" or "refunded".

![Workflow example - preview](../img/workflow_example_preview.jpg)

## Configuration

Pimcore ships with a sample configuration file at `/app/config/pimcore/workflowmanagement.example.php`. 
[See that file on GitHub](https://github.com/pimcore/pimcore/blob/master/app/config/pimcore/workflowmanagement.example.php).
This file should give you getting started in workflow configuration. 

For details of configuration options see comments in that file or [Configuration Details](./01_Configuration_Details.md).


## Events
WorkflowManagement comes with a number of events to hook into with the Pimcore 
[event manager](../10_Extending_Pimcore/11_Event_API_and_Event_Manager.md).

#### `workflowmanagement.preAction`
Fired BEFORE any action happens in the workflow. use this to hook into actions globally and define your own logic. i.e. 
validation or checks on other system vars

#### `workflowmanagement.postAction`
Fired AFTER any action happens in the workflow. Use this to hook into actions globally and define your own logic. i.e. 
trigger an email or maintenance job.

#### `workflowmanagement.preReturnAvailableActions`
Fired when returning the available actions to a user in the admin panel. use this to further customise what actions are 
available to a user. i.e. stop them logging time after 5pm ;)

#### Action specific events
There are also three more events that can be configured for individual actions and statuses to save you having to write 
logic to check the state / status each time. These are defined as follows under any action in the workflow configuration

```php
[
    "name" => "start_progress",                                
    ...
    "events" => [
        "before" => ['\\Website\\WorkflowExampleEventHandler', 'before'],
        "success" => ['\\Website\\WorkflowExampleEventHandler', 'success'],
        "failure" => ['\\Website\\WorkflowExampleEventHandler', 'failure']
    ],
],
```
The workflow manager will automatically attach and unattach these events to the action using the Pimcore 
[event manager](../10_Extending_Pimcore/11_Event_API_and_Event_Manager.md). 

The actions are also available in the event manager during any action and are identified as follows:
* `workflowmanagement.action.before`
* `workflowmanagement.action.success`
* `workflowmanagement.action.failure`

This way it is possible to create global events in `preAction` that can override custom state and status actions depending 
on your requirements.


## User Notifications
Email notifications can be configured to be sent to users when an action succeeds. To do this simply specify an array 
of user(s) or role(s) that you would like to be notified when an action happens to an element. Roles will send an email 
to every user with that role.

```php
[
    "name" => "start_progress",
    ...
    "notificationUsers" => [1,2,10]
]
```

## Workflow History
In the *"Notes & Events"* tab, there is a list with every action used on the object via the Workflow module.

![Notes & Events - notes from the workflow](../img/notesandevents_object_grid.png)



## Usage Example
Also have a look at our simple [Workflow Tutorial](./03_Workflow_Tutorial.md) to see Workflows in action or have a look
at the blog post series of our partners at Gather: 
 * [Part 1](https://www.gatherdigital.co.uk/community/post/pimcore-workflow-management-pt1/66)
 * [Part 2](https://www.gatherdigital.co.uk/community/post/pimcore-workflow-management-pt2/67) 
 * [Part 3](https://www.gatherdigital.co.uk/community/post/pimcore-workflow-management-pt3/70)

