# Code Resolver

Resolves the objects via your custom piece of code.

![Settings](../../../img/csvimport/code_resolver.png)

Sample implementation:

```php
<?php

use Pimcore\DataObject\Import\Resolver\AbstractResolver;

class MyCodeResolver extends AbstractResolver
{
    public function resolve(\stdClass $config, int $parentId, array $rowData)
    {
        $idColumn = $this->getIdColumn($config);
        $cellData = $rowData[$idColumn];

        $list = new Pimcore\Model\DataObject\News\Listing();
        $list->setCondition("title = " . $list->quote($cellData));
        $list->setLimit(1);
        $list = $list->load();

        if ($list) {
            $object = $list[0];
            return $object;
        }

        return null;
    }
}
```
