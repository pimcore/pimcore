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
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\DocType;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\DocType\Listing\Dao getDao()
 */
class Listing extends Model\Listing\JsonListing
{
    /**
     * Contains the results of the list. They are all an instance of Document\Doctype
     *
     * @var array
     */
    public $docTypes = [];

    /**
     * @return array
     */
    public function getDocTypes()
    {
        return $this->docTypes;
    }

    /**
     * @param array $docTypes
     *
     * @return $this
     */
    public function setDocTypes($docTypes)
    {
        $this->docTypes = $docTypes;

        return $this;
    }
}
