<?php


namespace Pimcore\Model\DataObject\ClassDefinition;


use Pimcore\Model\DataObject\Concrete;

interface PreviewGeneratorInterface
{

    /**
     * @param Concrete $object
     * @param array $params
     * @return string
     */
    public function generatePreviewUrl(Concrete $object, array $params): string;


    /**
     * @return array[
     *  [
     *  'name' => string,
     *  'label' => string,
     *  'values' => [
     *     string => string,
     *  ]
     *  ]
     * ]
     *
     */
    public function getPreviewConfig(Concrete $object): array;
}
