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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData;

class IndexFieldSelection
{
    /**
     * @var string
     */
    public $tenant;

    /**
     * @var string
     */
    public $field;

    /**
     * @var string|string[]
     */
    public $preSelect;

    /**
     * @param $field
     * @param $preSelect
     * @param $tenant
     */
    public function __construct($tenant, $field, $preSelect)
    {
        $this->field = $field;
        $this->preSelect = $preSelect;
        $this->tenant = $tenant;
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string|\string[] $preSelect
     */
    public function setPreSelect($preSelect)
    {
        $this->preSelect = $preSelect;
    }

    /**
     * @return string|\string[]
     */
    public function getPreSelect()
    {
        return $this->preSelect;
    }

    /**
     * @param string $tenant
     */
    public function setTenant($tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * @return string
     */
    public function getTenant()
    {
        return $this->tenant;
    }
}
