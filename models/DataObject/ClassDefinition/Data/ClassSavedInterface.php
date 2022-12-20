<?php

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject;

interface ClassSavedInterface
{
    /**
     * @param DataObject\ClassDefinition $class
     * @param array $params
     */
    public function classSaved($class/**, $params = [] **/);
}
