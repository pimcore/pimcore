# Support Strategies

The workflow engine offers several different ways to define which entities are supported by the configured workflow.

Important: The different configuration ways cannot be combined. It's necessary to choose one.

## Supports

The simplest way is to use `supports`. A single entity class name or an array of entity class names can be defined.

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

The expression support strategy can be used if a workflow should apply to a entity under certain circumstances only. 
It's possible to define a symfony expression - the workflow then applies only if the expression is valid.

##### Configuration Example

In the following example the workflow applies to products where the attribute "productType" is equal to "article".

```yaml
   support_strategy:
       type: expression
       arguments:
           - Pimcore\Model\DataObject\Product
           - "subject.getProductType() == 'article'"
```

## Custom Support Strategy

If a very specific logic is needed it's possible to add a service which implements the 
`Symfony\Component\Workflow\SupportStrategy\SupportStrategyInterface`.

##### Configuration Example

```yaml
   support_strategy:
       service: AppBundle\Workflow\SupportStrategy
```

##### Example Implementation (needs to be registered in the service container)

```php
<?php
namespace AppBundle\Workflow;

use Symfony\Component\Workflow\SupportStrategy\SupportStrategyInterface;
use Symfony\Component\Workflow\Workflow;

class SupportStrategy implements SupportStrategyInterface
{

    /**
     * @param Workflow $workflow
     * @param object   $subject
     *
     * @return bool
     */
    public function supports(Workflow $workflow, $subject)
    {
        if($subject instanceof \Pimcore\Model\DataObject\Test) {
            return true;
        }

        return false;
    }
}
```