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

##### Configuration Example

In the following example, the workflow applies to products where the attribute "productType" equals "article".

```yaml
   support_strategy:
       type: expression
       arguments:
           - Pimcore\Model\DataObject\Product
           - "subject.getProductType() == 'article'"
```

The expression uses the same expression language as workflow guards and permission conditions, so the current
user is available too (for example `is_granted('ROLE_PIMCORE_ADMIN')` or `'ROLE_PIMCORE_ADMIN' in role_names`).
See [Condition and guard expressions](./03_Permissions.md#condition-and-guard-expressions) for the full list of
available functions and variables.

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
