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

class Asset extends Model\Webservice\Data
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
    public $filename;

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $mimetype;

    /**
     * @var integer
     */
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;

    /**
     * @var integer
     */
    public $userOwner;

    /**
     * @var integer
     */
    public $userModification;

    /**
     * @var Webservice\Data\Property[]
     */
    public $properties;

    /**
     * @var object[]
     */
    public $customSettings;


    /**
     * @var
     */
    public $metadata;



    /**
     * @param $object
     * @param null $options
     */
    public function map($object, $options = null)
    {
        parent::map($object, $options);

        $settings = $object->getCustomSettings();
        if (!empty($settings)) {
            $this->customSettings = $settings;
        }

        $keys = get_object_vars($this);
        if (array_key_exists("childs", $keys)) {
            if ($object->hasChilds()) {
                $this->childs = [];
                foreach ($object->getChilds() as $child) {
                    $item = new Webservice\Data\Asset\Listing\Item();
                    $item->id = $child->getId();
                    $item->type = $child->getType();

                    $this->childs[] = $item;
                }
            }
        }

        $this->metadata = $object->getMetadata();
    }

    /**
     * @param $object
     * @param bool $disableMappingExceptions
     * @param null $idMapper
     * @throws \Exception
     */
    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null)
    {
        parent::reverseMap($object, $disableMappingExceptions, $idMapper);

        $metadata = $this->metadata;
        if (is_array($metadata)) {
            $metadata = json_decode(json_encode($metadata), true);
            $object->metadata = $metadata;
        }
    }
}
