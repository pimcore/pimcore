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
 * @package    Webservice
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Webservice\Data;

use Pimcore\Model;
use Pimcore\Model\Webservice;

abstract class Document extends Model\Webservice\Data
{

    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $parentId;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $key;

    /**
     * @var integer
     */
    public $index;

    /**
     * @var bool
     */
    public $published;

    /**
     * @var integer
     */
    public $userOwner;

    /**
     * @var Webservice\Data\Property[]
     */
    public $properties;

    /**
     * @param $object
     * @param null $options
     */
    public function map($object, $options = null)
    {
        parent::map($object);

        $keys = get_object_vars($this);
        if (array_key_exists("childs", $keys)) {
            if ($object->hasChilds()) {
                $this->childs = [];
                foreach ($object->getChilds() as $child) {
                    $item = new Webservice\Data\Document\Listing\Item();
                    $item->id = $child->getId();
                    $item->type = $child->getType();

                    $this->childs[] = $item;
                }
            }
        }
    }
}
