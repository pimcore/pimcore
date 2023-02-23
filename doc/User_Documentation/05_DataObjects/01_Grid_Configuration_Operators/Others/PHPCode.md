# PHP Code

![Symbol](../../../img/gridconfig/operator_phpcode_symbol.png)

Allows you to provide a custom operator implementation.

![Sample](../../../img/gridconfig/operator_phpcode_sample.png)

Sample implementation.

```php
<?php

namespace App\Operator;

use Pimcore\DataObject\GridColumnConfig\Operator\AbstractOperator;
use Pimcore\DataObject\GridColumnConfig\ResultContainer;

class OperatorSample extends AbstractOperator
{
    private $additionalData;
    
    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->additionalData = $config->additionalData;
    }

    public function getLabeledValue(array|ElementInterface $element): ResultContainer
    {
        $children = $this->getChildren();

        $result = new ResultContainer();
        $result->setValue($element->getId() . " huhu " .  count($children) . " " . $this->additionalData);

        return $result;
    }
}
```





