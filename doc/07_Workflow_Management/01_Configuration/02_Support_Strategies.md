---
title: Support Strategies
description: Control which elements a workflow applies to with class names, expressions, or custom services.
---

# Support Strategies

The workflow engine offers several ways to define which entities a configured workflow supports.

The different configuration methods cannot be combined. Choose one.

## Supports

Use `supports` to define a single entity class name or an array of entity class names.

##### Configuration Examples
```yaml
   supports: Pimcore\Model\DataObject\Product
```

```yaml
   supports:
       - Pimcore\Model\DataObject\Product
       - Pimcore\Model\DataObject\ProductCategory
```

## Expression Support Strategy

The expression support strategy applies a workflow to an entity only under certain circumstances.
Define a Symfony expression - the workflow applies only when the expression evaluates to true.

A blank expression (empty or whitespace only) is not evaluated: the workflow then applies to every entity of the
configured class name(s). Make sure an expression that is built from a parameter or environment variable
is not blank unintentionally, otherwise the workflow applies to all entities of the class.

##### Configuration Example

In the following example, the workflow applies to products where the attribute "productType" equals "article".

```yaml
   support_strategy:
       type: expression
       arguments:
           - Pimcore\Model\DataObject\Product
           - "subject.getProductType() == 'article'"
```

The expression uses the same expression language as permission conditions and global action guards. See
[Condition and guard expressions](./03_Permissions.md#condition-and-guard-expressions) for the full list of
available functions and variables. Only use the variables that describe the element (such as `subject`) here; see
the warning below for user-specific variables.

> **Warning:** Do not use user-specific checks (roles, `is_granted()`, `user`, `token`) in a support strategy to
> restrict who may *use* a workflow. The strategy decides whether the workflow applies to the element at all. When it
> evaluates to false for the current user, the workflow is treated as not applicable, so it is hidden for that user
> and none of its place permissions, transitions or global actions apply. This includes deny rules such as
> `modify: false`: they stop applying to that user, so the user is **less** restricted, not more. The expression is
> evaluated each time the workflow is looked up (it is not cached) and depends on who is logged in. Outside a login
> (CLI, workers) there is no user: `user` is the string `anonymous`, so a method call such as `user.getId()` throws an
> exception. Likewise, `is_fully_authenticated()` is a login-state check, not a Studio-vs-API switch: outside a
> full login the workflow does not apply and its restrictions (including deny rules) are lifted. Restrict access with permission
> `condition`s and transition `guard`s instead, and keep the support strategy to criteria of the element itself.

## Custom Support Strategy

For custom logic, add a service that implements
`Symfony\Component\Workflow\SupportStrategy\WorkflowSupportStrategyInterface`.

##### Configuration Example

```yaml
   support_strategy:
       service: App\Workflow\SupportStrategy
```

##### Example Implementation (register in the service container)

```php
<?php
namespace App\Workflow;

use Symfony\Component\Workflow\SupportStrategy\WorkflowSupportStrategyInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class SupportStrategy implements WorkflowSupportStrategyInterface
{
    public function supports(WorkflowInterface $workflow, object $subject): bool
    {
        if ($subject instanceof \Pimcore\Model\DataObject\Test) {
            return true;
        }

        return false;
    }
}
```
