<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


namespace Pimcore\Model\Object\Data;

class IndexFieldSelection {

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
    function __construct($tenant, $field, $preSelect)
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