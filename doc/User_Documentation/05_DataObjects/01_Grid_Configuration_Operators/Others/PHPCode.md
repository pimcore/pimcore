# PHP Code

![Symbol](../../../img/gridconfig/operator_phpcode_symbol.png)

Allows you to provide a custom operator implementation.

![Sample](../../../img/gridconfig/operator_phpcode_sample.png)

Sample implementation.
```php
<?php

namespace AppBundle\Operator;

use Pimcore\DataObject\GridColumnConfig\Operator\AbstractOperator;
use Pimcore\DataObject\GridColumnConfig\ResultContainer;

class OperatorSample extends AbstractOperator
{
    private $additionalData;
    
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->additionalData = $config->additionalData;
    }

    public function getLabeledValue($element)
    {
        $childs = $this->getChilds();

        $result = new ResultContainer();
        $result->setValue($element->getId() . " huhu " .  count($childs) . " " . $this->additionalData);

        return $result;
    }
}
```





