<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */
namespace Pimcore\Bundle\PimcoreBundle\Service\Tool;

/**
 * Class ImplementationLocator
 *
 * finds concrete implementations based on configuration on symfony container and returns class names
 *
 */
class ImplementationLocator {

    /**
     * @var array
     */
    protected $pimcoreConfig;

    /**
     * ImplementationLocator constructor.
     * @param $pimcoreConfig
     */
    public function __construct($pimcoreConfig)
    {
        $this->pimcoreConfig = $pimcoreConfig;
    }


    /**
     * Finds implementation for layout and data components of class definitions
     *
     * @param $dataType - type of component, either data or layout
     * @param $fieldType - name of component
     * @return null|string
     */
    public function getObjectClassDefinitionImplementation($dataType, $fieldType) {

        $class = $this->pimcoreConfig['objects']['class_definitions'][$dataType][$fieldType];
        if (!\Pimcore\Tool::classExists($class)) {
            $class = "\\Pimcore\\Model\\Object\\ClassDefinition\\".ucfirst($dataType)."\\" . ucfirst($fieldType);
            if (!\Pimcore\Tool::classExists($class)) {
                $class = "\\Object_Class_" .ucfirst($dataType)."_" . ucfirst($fieldType);
                if (!\Pimcore\Tool::classExists($class)) {
                    $class = null;
                }
            }
        }
        return $class;
    }


}