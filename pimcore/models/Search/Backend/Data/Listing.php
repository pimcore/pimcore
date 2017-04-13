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

namespace Pimcore\Model\Search\Backend\Data;

/**
 * @method \Pimcore\Model\Search\Backend\Data\Listing\Dao getDao()
 */
class Listing extends \Pimcore\Model\Listing\AbstractListing
{
    /**
     * @var array
     */
    public $entries;

    /**
     * @var array
     */
    public $validOrderKeys = [
        'id',
        'fullpath',
        'maintype',
        'type',
        'subtype',
        'published',
        'creationDate',
        'modificationDate',
        'userOwner',
        'userModification'
    ];

    /**
     * @return array
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * @param $entries
     *
     * @return $this
     */
    public function setEntries($entries)
    {
        $this->entries = $entries;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->initDao('\\Pimcore\\Model\\Search\\Backend\\Data\\Listing');
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        if (in_array($key, $this->validOrderKeys)) {
            return true;
        }

        return false;
    }
}
