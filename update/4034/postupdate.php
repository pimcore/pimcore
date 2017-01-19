<?php

$list = new \Pimcore\Model\Object\Objectbrick\Definition\Listing();
$list = $list->load();

if (is_array($list))
{
    foreach ($list as $brickDefinition)
    {
        if ($brickDefinition instanceof \Pimcore\Model\Object\Objectbrick\Definition)
        {
            $classDefinitions = $brickDefinition->getClassDefinitions();

            if (is_array($classDefinitions))
            {
                foreach ($classDefinitions as &$classDefinition)
                {
                    $definition = \Pimcore\Model\Object\ClassDefinition::getById($classDefinition['classname']);

                    $classDefinition['classname'] = $definition->getName();
                }
            }

            $brickDefinition->setClassDefinitions($classDefinitions);
            $brickDefinition->save();
        }
    }
}
