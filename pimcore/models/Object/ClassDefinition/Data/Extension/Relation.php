<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 12.01.2016
 * Time: 13:08
 */

namespace Pimcore\Model\Object\ClassDefinition\Data\Extension;

/**
 * Class Relation
 *
 * @package Pimcore\Model\Object\ClassDefinition\Data\Extension
 * @method bool getDocumentsAllowed()
 * @method bool getAssetsAllowed()
 * @method bool getObjectsAllowed()
 * @method string[] getDocumentTypes()
 * @method string[] getAssetTypes()
 * @method string[] getClasses()
 */
trait Relation
{
    /**
     * @param bool|false $asArray
     *
     * @return string[]
     */
    protected function getPhpDocClassString($asArray = false)
    {
        // init
        $class = [];
        $strArray = $asArray ? '[]' : '';


        // add documents
        if (method_exists($this, 'getDocumentsAllowed') && $this->getDocumentsAllowed()) {
            $documentTypes = $this->getDocumentTypes();
            if (count($documentTypes) == 0) {
                $class[] = '\Pimcore\Model\Document\Page' . $strArray;
                $class[] = '\Pimcore\Model\Document\Snippet' . $strArray;
                $class[] = '\Pimcore\Model\Document' . $strArray;
            } elseif (is_array($documentTypes)) {
                foreach ($documentTypes as $item) {
                    $class[] = sprintf('\Pimcore\Model\Document\%s', $item['documentTypes'] . $strArray);
                }
            }
        }


        // add asset
        if (method_exists($this, 'getAssetsAllowed') && $this->getAssetsAllowed()) {
            $assetTypes = $this->getAssetTypes();
            if (count($assetTypes) == 0) {
                $class[] = '\Pimcore\Model\Asset' . $strArray;
            } elseif (is_array($assetTypes)) {
                foreach ($assetTypes as $item) {
                    $class[] = sprintf('\Pimcore\Model\Asset\%s', $item['assetTypes'] . $strArray);
                }
            }
        }


        // add objects
        if ($this->getObjectsAllowed()) {
            $classes = $this->getClasses();
            if (count($classes) == 0) {
                $class[] = '\Pimcore\Model\Object\AbstractObject' . $strArray;
            } elseif (is_array($classes)) {
                foreach ($this->getClasses() as $item) {
                    $class[] = sprintf('\Pimcore\Model\Object\%s', $item['classes'] . $strArray);
                }
            }
        }

        return $class;
    }
}
