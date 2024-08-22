<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\Data;

use Exception;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition;
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

    public const TABLE_NAME = 'object_url_slugs';

    protected int $objectId;

    protected string $classId;

    protected ?string $slug = null;

    protected ?int $siteId = null;

    protected string $fieldname;

    protected string $ownertype;

    protected string $ownername;

    protected string $position;

    protected ?string $previousSlug = null;

    /**
     * UrlSlug constructor.
     *
     */
    public function __construct(?string $slug, ?int $siteId = 0)
    {
        $this->slug = $slug;
        $this->siteId = $siteId ?? 0;
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function setObjectId(int $objectId): static
    {
        $this->objectId = $objectId;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @internal
     *
     */
    public function getPreviousSlug(): ?string
    {
        return $this->previousSlug;
    }

    /**
     * @internal
     *
     */
    public function setPreviousSlug(?string $previousSlug): void
    {
        $this->previousSlug = $previousSlug;
    }

    public function getSiteId(): ?int
    {
        return $this->siteId;
    }

    public function setSiteId(?int $siteId): static
    {
        $this->siteId = $siteId ?? 0;

        return $this;
    }

    public function getFieldname(): ?string
    {
        return $this->fieldname;
    }

    public function setFieldname(?string $fieldname): static
    {
        $this->fieldname = $fieldname;

        return $this;
    }

    public function getOwnertype(): ?string
    {
        return $this->ownertype;
    }

    public function setOwnertype(?string $ownertype): static
    {
        $this->ownertype = $ownertype;

        return $this;
    }

    public function getOwnername(): ?string
    {
        return $this->ownername;
    }

    public function setOwnername(?string $ownername): static
    {
        $this->ownername = $ownername;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getClassId(): string
    {
        return $this->classId;
    }

    public function setClassId(string $classId): static
    {
        $this->classId = $classId;

        return $this;
    }

    public static function createFromDataRow(array $rawItem): UrlSlug
    {
        $slug = new self($rawItem['slug'], $rawItem['siteId']);
        $slug->setObjectId($rawItem['objectId']);
        $slug->setClassId($rawItem['classId']);
        $slug->setFieldname($rawItem['fieldname']);
        $slug->setOwnertype($rawItem['ownertype']);
        $slug->setOwnername($rawItem['ownername']);
        $slug->setPosition($rawItem['position']);
        $slug->setPreviousSlug($rawItem['slug']);

        return $slug;
    }

    /**
     *
     *
     * @internal
     */
    public static function resolveSlug(string $path, int $siteId = 0): ?UrlSlug
    {
        $cacheKey = self::getCacheKey($path, $siteId);
        if (RuntimeCache::isRegistered($cacheKey)) {
            $slug = RuntimeCache::get($cacheKey);

            if ($slug instanceof UrlSlug) {
                return $slug;
            }
        }

        $slug = null;
        $db = Db::get();

        try {
            $filterSiteId = 'siteId = 0';
            if ($siteId) {
                $filterSiteId = sprintf('(siteId = %d OR siteId = 0)', $siteId);
            }

            $query = sprintf(
                'SELECT * FROM %s WHERE slug = %s AND %s ORDER BY siteId DESC LIMIT 1',
                self::TABLE_NAME,
                $db->quote($path),
                $filterSiteId
            );

            $rawItem = $db->fetchAssociative($query);

            if ($rawItem) {
                $slug = self::createFromDataRow($rawItem);
            }
        } catch (Exception $e) {
            Logger::error((string) $e);
        }

        RuntimeCache::set($cacheKey, $slug);

        return $slug;
    }

    /**
     * @internal
     *
     * @throws Exception
     */
    public function getAction(): string
    {
        /** @var ClassDefinition\Data\UrlSlug $fd */
        $fd = null;

        $classDefinition = ClassDefinition::getById($this->getClassId());

        if ($classDefinition) {
            // reverse look up the field definition ...
            if ($this->getOwnertype() === 'object') {
                $fd = $classDefinition->getFieldDefinition($this->getFieldname());
            } elseif ($this->getOwnertype() === 'localizedfield') {
                $ownerName = $this->getOwnername();
                if (str_contains($ownerName, '~')) {
                    // this is a localized field inside a field collection or objectbrick
                    $parts = explode('~', $this->getOwnername());
                    $type = trim($parts[0], '/');
                    $objectFieldnameParts = $this->getOwnername();
                    $objectFieldnameParts = explode('~', $objectFieldnameParts);
                    $objectFieldnameParts = $objectFieldnameParts[1];
                    $objectFieldname = explode('/', $objectFieldnameParts);
                    $objectFieldname = $objectFieldname[0];

                    if ($type == 'objectbrick') {
                        $objectFieldDef = $classDefinition->getFieldDefinition($objectFieldname);
                        if ($objectFieldDef instanceof Objectbricks) {
                            $allowedBricks = $objectFieldDef->getAllowedTypes();
                            foreach ($allowedBricks as $allowedBrick) {
                                $brickDef = Definition::getByKey($allowedBrick);
                                if ($brickDef instanceof Definition) {
                                    $lfDef = $brickDef->getFieldDefinition('localizedfields');
                                    if ($lfDef instanceof Localizedfields) {
                                        $fd = $lfDef->getFieldDefinition($this->getFieldname());

                                        break;
                                    }
                                }
                            }
                        }
                    } elseif ($type == 'fieldcollection') {
                        // note that for fieldcollections we need the object data for resolving the
                        // fieldcollection type. alternative: store the fc type as well (similar to class id)
                        $object = Concrete::getById($this->getObjectId());
                        $getter = 'get' . ucfirst($objectFieldname);
                        if ($object && method_exists($object, $getter)) {
                            $fc = $object->$getter();
                            if ($fc instanceof Fieldcollection) {
                                $index = explode('/', $objectFieldnameParts);
                                $index = (int) $index[1];
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
                    $lfDef = $classDefinition->getFieldDefinition('localizedfields');
                    if ($lfDef instanceof Localizedfields) {
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

                // note that for fieldcollections we need the object data for resolving the
                // fieldcollection type. alternative: store the fc type as well (similar to class id)
                $object = Concrete::getById($this->getObjectId());

                if (method_exists($object, $getter)) {
                    $fcValue = $object->$getter();
                    if ($fcValue instanceof Fieldcollection) {
                        // https://github.com/pimcore/pimcore/issues/13435#issuecomment-1287052907
                        $item = $fcValue->get(0);
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

            throw new Exception('Could not resolve field definition for slug: ' . $this->getSlug(). '. Remove it!');
        }

        return $fd->getAction();
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        $db = Db::get();
        $db->delete(self::TABLE_NAME, ['slug' => $this->getSlug(), 'siteId' => $this->getSiteId()]);

        RuntimeCache::set(self::getCacheKey($this->getSlug(), $this->getSiteId()), null);
    }

    /**
     *
     * @throws Exception
     */
    public static function handleSiteDeleted(int $siteId): void
    {
        $db = Db::get();
        $db->delete(self::TABLE_NAME, ['siteId' => $siteId]);
    }

    /**
     *
     * @throws Exception
     */
    public static function handleClassDeleted(string $classId): void
    {
        $db = Db::get();
        $db->delete(self::TABLE_NAME, ['classId' => $classId]);
    }

    /**
     *
     *
     * @internal
     */
    protected static function getCacheKey(string $path, int $siteId): string
    {
        return "UrlSlug~~{$path}~~{$siteId}";
    }
}
