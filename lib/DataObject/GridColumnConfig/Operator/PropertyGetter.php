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
 * @package    Object
 *
 * @author     Michal Bolka <mbolka@divante.co>
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\GridColumnConfig\Operator;

class PropertyGetter extends AbstractOperator
{
    private $propertyName;

    /**
     * AnyPropertyGetter constructor.
     *
     * @param \stdClass $config
     * @param null      $context
     */
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->propertyName = $config->propertyName;
    }

    /**
     * @param \Pimcore\Model\Element\ElementInterface $element
     *
     * @return \stdClass
     */
    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $properties = $element->getProperties();

        if (array_key_exists($this->getPropertyName(), $properties)) {
            $result->value = $properties[$this->getPropertyName()]->getObjectVar('data');
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * @param mixed $propertyName
     */
    public function setPropertyName($propertyName)
    {
        $this->propertyName = $propertyName;
    }
}
