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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\ObjectVarTrait;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class UrlSlug implements OwnerAwareFieldInterface
{
    use ObjectVarTrait;
    use OwnerAwareFieldTrait;

    /**
     * @var int
     */
    protected $objectId;

    /**
     * @var string|null
     */
    protected $slug;

    /**
     * @var int|null
     */
    protected $siteId;

    /**
     * @var string
     */
    protected $fieldname;

    /**
     * @var int
     */
    protected $index;

    /**
     * @var string
     */
    protected $ownertype;

    /**
     * @var string
     */
    protected $ownername;

    /**
     * @var string
     */
    protected $position;

    /**
     * UrlSlug constructor.
     * @param string $slug
     * @param int|null $siteId
     */
    public function __construct(?string $slug, ?int $siteId = null)
    {
        $this->slug = $slug;
        $this->siteId = $siteId;
    }

    /**
     * @return int
     */
    public function getObjectId(): int
    {
        return $this->objectId;
    }


    /**
     * @param int $objectId
     * @return $this
     */
    public function setObjectId(int $objectId)
    {
        $this->objectId = $objectId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }


    /**
     * @param string|null $slug
     * @return $this
     */
    public function setSlug(?string $slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return int
     */
    public function getSiteId(): ?int
    {
        return $this->siteId;
    }


    /**
     * @param int|null $siteId
     * @return $this
     */
    public function setSiteId(?int $siteId)
    {
        $this->siteId = $siteId ?? 0;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldname(): ?string
    {
        return $this->fieldname;
    }


    /**
     * @param string|null $fieldname
     * @return $this
     */
    public function setFieldname(?string $fieldname)
    {
        $this->fieldname = $fieldname;
        return $this;
    }

    /**
     * @return int
     */
    public function getIndex(): ?int
    {
        return $this->index;
    }


    /**
     * @param int|null $index
     * @return $this
     */
    public function setIndex(?int $index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOwnertype(): ?string
    {
        return $this->ownertype;
    }


    /**
     * @param string|null $ownertype
     * @return $this
     */
    public function setOwnertype(?string $ownertype)
    {
        $this->ownertype = $ownertype;
        return $this;
    }

    /**
     * @return string
     */
    public function getOwnername(): ?string
    {
        return $this->ownername;
    }


    /**
     * @param string|null $ownername
     * @return $this
     */
    public function setOwnername(?string $ownername)
    {
        $this->ownername = $ownername;
        return $this;
    }

    /**
     * @return string
     */
    public function getPosition(): ?string
    {
        return $this->position;
    }

    /**
     * @param string|null $position
     * @return $this
     */
    public function setPosition(?string $position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @param array $rawItem
     * @return UrlSlug
     */
    public static function createFromDataRow($rawItem) : UrlSlug {
        $slug = new self($rawItem['slug'], $rawItem['siteId']);
        $slug->setObjectId($rawItem['objectId']);
        $slug->setFieldname($rawItem['fieldname']);
        $slug->setIndex($rawItem['index']);
        $slug->setOwnertype($rawItem['ownertype']);
        $slug->setOwnername($rawItem['ownername']);
        $slug->setPosition($rawItem['position']);
        return $slug;
    }

    /**
     * @param string $path
     * @param int $siteId
     * @return UrlSlug|null
     */
    public static function resolveSlug($path, $siteId = 0) {
        $db = Db::get();
        try {
            $query = 'SELECT * FROM object_url_slugs WHERE slug = ' . $db->quote($path)
                . " AND siteId = " . $db->quote($siteId);

            $rawItem = $db->fetchRow($query);

            if ($rawItem) {
                $slug = self::createFromDataRow($rawItem);
                return $slug;
            }


        } catch (\Exception $e) {
            Logger::error($e);
        }
        return null;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getAction() {
        /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\UrlSlug $fd */
        $fd = null;

        $object = Concrete::getById($this->getObjectId());

        if ($object) {

            // reverse look up the field definition ...
            if ($this->getOwnertype() === 'object') {
                $fd = $object->getClass()->getFieldDefinition($this->getFieldname());
            } elseif ($this->getOwnertype() === 'localizedfield') {
                $ownerName = $this->getOwnername();
                if (strpos($ownerName, '~') !== false) {
                    // this is a localized field inside a field collection objectbrick
                    $parts = explode('~', $this->getOwnername());
                    $type = trim($parts[0], '/');
                    $objectFieldnameParts = $this->getOwnername();
                    $objectFieldnameParts = explode('~', $objectFieldnameParts);
                    $objectFieldnameParts = $objectFieldnameParts[1];
                    $objectFieldname = explode('/', $objectFieldnameParts);
                    $objectFieldname = $objectFieldname[0];

                    if ($type == 'objectbrick') {
                        if ($objectFieldDef = $object->getClass()->getFieldDefinition($objectFieldname)) {
                            /** @var Objectbricks $objectFieldDef */

                            /** @var Objectbricks $allowedBricks */
                            $allowedBricks = $objectFieldDef->getAllowedTypes();
                            if (is_array($allowedBricks)) {
                                foreach ($allowedBricks as $allowedBrick) {
                                    /** @var Definition $brickDef */
                                    $brickDef = Definition::getByKey($allowedBrick);
                                    if ($lfDef = $brickDef->getFieldDefinition('localizedfields')) {
                                        /** @var Localizedfields $lfDef */
                                        $fd = $lfDef->getFieldDefinition($this->getFieldname());
                                        break;
                                    }
                                }
                            }
                        }
                    } else if ($type == 'fieldcollection') {
                        $getter = 'get' . ucfirst($objectFieldname);
                        if (method_exists($object, $getter)) {
                            $fc = $object->$getter();
                            if ($fc instanceof Fieldcollection) {
                                $index = explode('/', $objectFieldnameParts);
                                $index = $index [1];
                                $item = $fc->get($index);
                                if ($item instanceof AbstractData) {
                                    if ($colDef = Fieldcollection\Definition::getByKey($item->getType())) {
                                        $lfDef = $colDef->getFieldDefinition('localizedfields');
                                        if ($lfDef instanceof Localizedfields) {
                                            $fd = $lfDef->getFieldDefinition($this->getFieldname());
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    if ($lfDef = $object->getClass()->getFieldDefinition('localizedfields')) {
                        /** @var Localizedfields $lfDef */
                        $fd = $lfDef->getFieldDefinition($this->getFieldname());
                    }
                }
            } elseif ($this->getOwnertype() === 'objectbrick') {
                $brickDef = Definition::getByKey($this->getPosition());
                if ($brickDef) {
                    $fd = $brickDef->getFieldDefinition($this->getFieldname());
                }
            } elseif ($this->getOwnertype() == 'fieldcollection') {
                $ownerName = $this->getOwnername();
                $getter = 'get' . ucfirst($ownerName);
                if (method_exists($object, $getter)) {
                    $fcValue = $object->$getter();
                    if ($fcValue instanceof Fieldcollection) {
                        $item = $fcValue->get($this->getPosition());
                        $fcType = $item->getType();
                        if ($fcDef = Fieldcollection\Definition::getByKey($fcType)) {
                            $fd = $fcDef->getFieldDefinition($this->getFieldname());
                        }
                    }
                }
            }
        }

        if (!$fd instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\UrlSlug) {
            // slug could not be resolved which means that the data model has changed in the meantime, delete me.
            $this->delete();


            throw new \Exception("Could not resolve field definition for slug: " . $this->getSlug(). ". Remove it!");
        }

        return $fd->getAction();
    }

    public function delete() {
        $db = Db::get();
        $count = $db->delete('object_url_slugs', ['slug' => $this->getSlug(), 'siteId' => $this->getSiteId()]);
    }
}
