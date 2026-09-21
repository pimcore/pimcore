---
title: Notifications
description: Send email and in-app notifications to users and roles when transitions occur.
---

# Notifications

Configure email or Pimcore notifications to notify users when a transition takes place.
Specify an array of users or roles in the `notificationSettings` of a transition definition.
Roles send a notification to every user assigned that role.

```yaml
...
    transitions:
        myTransition:
            options:
                notificationSettings:
                    -
                      # A Symfony expression to match this notification setting. All matching sets apply.
                      condition: "" # optional

                      # Send an email notification to a list of users (user names) when the transition gets applied
                      notifyUsers: ['admin']

                      # Send an email notification to a list of user roles (role names) when the transition gets applied
                      notifyRoles: ['projectmanagers', 'admins']

                      # Channel for the notification: "mail" and/or "pimcore_notification" (default: "mail")
                      channelType:
                         - mail
                         - pimcore_notification

                      # Type of mail source
                      mailType: 'template' # One of "template"; "pimcore_document"

                      # Path to mail source - Symfony template path or full path to a Pimcore document.
                      # Use %%_locale%% as placeholder for language.
                      mailPath: '@PimcoreCore/Workflow/NotificationEmail/notificationEmail.html.twig'
...
```

Multiple notification settings with conditions allow sophisticated notifications per transition.

## Customizing the Email Template

- **Override the default template** at `@PimcoreCore/Workflow/NotificationEmail/notificationEmail.html.twig`
  or configure a custom template path in settings.
  Default parameters available in the template: `subjectType`, `subject`, `action`, `workflow`,
  `workflowName`, `deeplink`, `note_description`, `translator`, `lang`.
  For additional parameters, override the service `Pimcore\Workflow\Notification\NotificationEmailService`.

- **Use a Pimcore Mail Document** for full access to Pimcore Mail Document features
  (controller, action, placeholders). The same parameters as above are available.

- **Use custom event listeners** for notification requirements beyond email and in-app notifications.
  See [Working with PHP API](../07_Working_with_PHP_API.md) for event handling details.
