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
        if(method_exists($this, 'getDocumentsAllowed') && $this->getDocumentsAllowed())
        {
            if(count($this->getDocumentTypes()) == 0)
            {
                $class[] = '\Pimcore\Model\Document\Page' . $strArray;
                $class[] = '\Pimcore\Model\Document\Snippet' . $strArray;
                $class[] = '\Pimcore\Model\Document' . $strArray;
            }
            else
            {
                foreach($this->getDocumentTypes() as $item)
                {
                    $class[] = sprintf('\Pimcore\Model\Document\%s', $item['documentTypes'] . $strArray);
                }
            }
        }


        // add asset
        if(method_exists($this, 'getAssetsAllowed') && $this->getAssetsAllowed())
        {
            if(count($this->getAssetTypes()) == 0)
            {
                $class[] = '\Pimcore\Model\Asset' . $strArray;
            }
            else
            {
                foreach($this->getAssetTypes() as $item)
                {
                    $class[] = sprintf('\Pimcore\Model\Asset\%s', $item['assetTypes'] . $strArray);
                }
            }
        }


        // add objects
        if($this->getObjectsAllowed())
        {
            if(count($this->getClasses()) == 0)
            {
                $class[] = '\Pimcore\Model\Object\AbstractObject' . $strArray;
            }
            else
            {
                foreach($this->getClasses() as $item)
                {
                    $class[] = sprintf('\Pimcore\Model\Object\%s', $item['classes'] . $strArray);
                }
            }
        }


        return $class;
    }
}