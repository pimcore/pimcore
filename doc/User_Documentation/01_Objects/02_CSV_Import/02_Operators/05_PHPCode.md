# Operator PHPCode

![Setting](../../../img/gridconfig/operator_phpcode_symbol.png)

Allows you to provide a custom setter implementation.

![Settings](../../../img/csvimport/operator_PHPCode.png)

Sample implementation: Unpublishes the object if the creation date was before 2017-11-17 which is CSV column 8 in this example.
In addition,it replaces the short text.

```php
<?php

class MyImportCodeOperator extends \Pimcore\Model\DataObject\ImportColumnConfig\Operator\AbstractOperator
{

    /**
     * MyImportCodeOperator constructor.
     * @param $config
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->params = $config->resolverSettings->params;
    }

    /**
     * @param $element
     * @param $target
     * @param $rowData
     * @param $rowIndex
     *
     * @return mixed
     */
    public function process($element, &$target, &$rowData, $colIndex, &$context = [])
    {
        $colData = $rowData[$colIndex];
        $target->setPublished($colData > 1510931949);
        if (!$target->getPublished()) {
            $target->setShortText("not available anymore", "en");
        }

    }
}
```

![Preview](../../../img/csvimport/operator_PHPCode2.png)

