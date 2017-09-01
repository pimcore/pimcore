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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

/**
 * Abstract base class for filter definition type field collections
 */
abstract class AbstractFilterDefinitionType extends \Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData
{
    protected $metaData = [];

    /**
     * @return array
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * @param array $metaData
     *
     * @return $this
     */
    public function setMetaData($metaData)
    {
        $this->metaData = $metaData;

        return $this;
    }

    /**
     * @return string
     */
    abstract public function getLabel();

    /**
     * @return string
     */
    abstract public function getField();

    /**
     * @return string
     */
    abstract public function getScriptPath();

    /**
     * @return string
     */
    public function getRequiredFilterField()
    {
        return '';
    }
}
