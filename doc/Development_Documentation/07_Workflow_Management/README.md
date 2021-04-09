# Workflow Management

## General
Pimcore Workflow Management provides configuration of multiple workflows on Pimcore elements (assets, documents, data 
objects) to support data maintenance processes, element life cycles and various other processes.   

It is based on the [Symfony workflow component](https://symfony.com/doc/3.4/workflow.html) and extends it 
with a few Pimcore specific features. So, before using Pimcore Workflow Management it makes sense to know 
the basics of Symfony workflow component.


### Concepts 
**Workflow** 
A workflow is a model of a process an element in Pimcore goes through. It consists of *places*, *transitions*, a 
*marking store*, *global actions* and a couple of additional configuration options. Each element can be in multiple 
workflows simultaneously. 

**Workflow Type 'Workflow'**
Workflow type *workflow* describes the default type of workflows and allows to model a *workflow net* which is a subclass 
of a *petri net*. It models a process of an element and allows multiple places simultaneously for this element. 

**Workflow Type 'State Machine'**
A state machine is a subset of a workflow and its purpose is to hold a state of your model. The most important restriction
is that a state machine cannot be in more than one place simultaneously. 
For further details see also [Symfony docs](https://symfony.com/doc/3.4/workflow/state-machines.html). 

**Place**
A place is a step in the workflow and describes a characteristic or a status of an element - for example *in progress*, 
*product attributes defined*, *copyright available*, *element ready for publish*, etc.  
Depending on the place an element may appear in a specific view (e.g. custom layout for data objects) and may have certain
special permissions (e.g. finished elements cannot be modified anymore). 

**Marking Store**
The marking store stores the current place(s) for each element. Pimcore ships with a couple of stores that can be configured
in [workflow configuration](./01_Configuration_Details.md). 

**Transition**
A transition describes the action to get from one place to another. Transitions are allowed (or not) depending on additional
criteria (transition guards) and may require additional notes and information entered by user.  

**Transition Guard**
Define criteria that define if a transition is currently allowed or not. 

**Global Action**
While transitions are only available when the element is in a certain place, global actions are available at every place. 
Besides that, they are very similar to transitions. 


## Configuration
The workflow configuration takes place in the Symfony configuration tree in the Pimcore namespace. For details of 
configuration options see inline comments and documentation (call command `bin/console config:dump-reference PimcoreCoreBundle`)
or [Configuration Details](./01_Configuration_Details.md).


## Events

The Pimcore workflow management fires several events that can be used to customize and extend functionality. For details
see [Working with PHP API](./09_Working_with_PHP_API.md).


## User Notifications
Notifications (via email or Pimcore notifications) can be configured to be sent to users when an transition takes place. 
To do this simply specify an array of user(s) or role(s) that you would like to be notified in options section of the 
transition definition. 

Roles will send an notification to every user with that role.

```yml
...
    transitions:
        myTransition:
            options:
                notificationSettings:
                    - 
                      # A symfony expression can be configured here. All sets of notification which are matching the condition will be used.
                      condition: "" # optional some condition that should apply for this notification setting to be executed
                      
                      # Send a email notification to a list of users (user names) when the transition get's applied
                      notifyUsers: ['admin']
                      
                      # Send a email notification to a list of user roles (role names) when the transition get's applied
                      notifyRoles: ['projectmanagers', 'admins']
                      
                      # Define which channel notification should be sent to, possible values "mail" and "pimcore_notification", default value is "mail".
                      channelType:
                         - mail
                         - pimcore_notification
                      
                      # Type of mail source. 
                      mailType: 'template' # this is the default value, One of "template"; "pimcore_document"
                      
                      # Path to mail source - either Symfony path to template or fullpath to Pimcore document. 
                      # Optional use %%_locale%% as placeholder for language.
                      mailPath: '@PimcoreCore/Workflow/NotificationEmail/notificationEmail.html.twig' #this is the value
...
```

Multiple notification settings with conditions allow sophisticated notifications to be configured for each transition. 
To customize the e-mail template, following options are available: 
- Overwrite the template `@PimcoreCore/Workflow/NotificationEmail/notificationEmail.html.twig` or configure your own 
  template path in settings. Default parameters available in the template are `subjectType`, `subject`, `action`, `workflow`, 
  `workflowName`, `deeplink`, `note_description`, `translator`, `lang`. If additional parameters are required, overwrite 
  the service `Pimcore\Workflow\Notification\NotificationEmailService`.

- Configure a Pimcore Mail Document and use full power of Pimcore Mail Documents, with Controller, Action, Placeholders, 
  etc. In the mail document same parameters as above are available.    
  
- If more custom notifications are necessary, use custom event listeners. 

## Workflow History
In the *"Notes & Events"* tab, there is a list with every action used on the object via the Workflow module.

![Notes & Events - notes from the workflow](../img/notesandevents_object_grid.png)

## Workflow Overview

If workflows are configured for a Pimcore element, an additional tab with workflow details like all configured workflows, 
their current places and a workflow graph is added to Pimcore element detail page. 

![Workflow Overview](../img/workflow-overview.jpg)

> To render the graph, `graphviz` is needed as additional system requirement. 