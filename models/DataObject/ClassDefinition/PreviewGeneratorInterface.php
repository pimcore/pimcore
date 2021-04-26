<?php


namespace Pimcore\Model\DataObject\ClassDefinition;


interface PreviewGeneratorInterface
{

    /**
     * @param \Pimcore\Model\DataObject\Concrete $object
     * @param array $params
     * @return string
     */
    public function generatePreviewUrl(\Pimcore\Model\DataObject\Concrete $object, array $params): string;


    /**
     * @return array
     */
    public function getParams(\Pimcore\Model\DataObject\Concrete $object): array;
}
