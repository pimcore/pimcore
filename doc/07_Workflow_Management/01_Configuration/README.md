---
title: Configuration
description: Configure workflow places, transitions, marking stores, permissions, and notifications in the Symfony configuration tree.
---

# Configuration

Workflow configuration uses the Symfony configuration tree under the `pimcore.workflows` namespace.
Define workflows in your project's configuration files (e.g. `config/config.yaml`)
or run `bin/console config:dump-reference PimcoreCoreBundle` for inline documentation.

A workflow definition covers places, transitions, global actions, and several aspects
described on their own pages:

- **[Marking Stores](./01_Marking_Stores.md)** - Choose how workflow places are persisted.
  The default `state_table` store works for all element types.
  For data objects, attribute-based stores (`single_state`, `data_object_multiple_state`,
  `data_object_splitted_state`) store places directly in the object model.

- **[Support Strategies](./02_Support_Strategies.md)** - Control which elements a workflow applies to.
  Use class names for simple filtering, Symfony expressions for conditional matching,
  or a custom service for advanced logic.

- **[Permissions](./03_Permissions.md)** - Override element permissions (save, publish, delete)
  based on the current workflow place. Conditions allow different permission sets per user role.

- **[Notifications](./04_Notifications.md)** - Send email or Pimcore in-app notifications
  to users and roles when transitions occur. Each transition supports multiple notification
  settings with conditions for fine-grained control.

The [Configuration Details](./05_Configuration_Details/README.md) page provides the complete
YAML reference with all available options and inline comments.
