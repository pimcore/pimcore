<?php

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

namespace Pimcore\Model;

use Pimcore\Event\Model\VersionEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Event\VersionEvents;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionMatcher;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionReplaceFilter;
use Pimcore\Model\Element\ElementDumpStateInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
use Pimcore\Model\Version\SetDumpStateFilter;
use Pimcore\Tool\Serialize;

/**
 * @method \Pimcore\Model\Version\Dao getDao()
 */
final class Version extends AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var int
     */
    protected $cid;

    /**
     * @var string
     */
    protected $ctype;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var User|null
     */
    protected ?User $user = null;

    /**
     * @var string
     */
    protected $note;

    /**
     * @var int
     */
    protected $date;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var bool
     */
    protected $public = false;

    /**
     * @var bool
     */
    protected $serialized = false;

    /**
     * @var string|null
     */
    protected $stackTrace = '';

    /**
     * @var bool
     */
    protected $generateStackTrace = true;

    /**
     * @var int
     */
    protected $versionCount = 0;

    /**
     * @var string|null
     */
    protected $binaryFileHash;

    /**
     * @var int|null
     */
    protected $binaryFileId;

    /**
     * @var bool
     */
    public static $disabled = false;

    /**
     * @var bool
     */
    protected bool $autoSave = false;

    /**
     * @var string|null
     */
    protected ?string $storageType = null;

    protected VersionStorageAdapterInterface $storageAdapter;

    public function __construct()
    {
        $this->storageAdapter = \Pimcore::getContainer()->get(VersionStorageAdapterInterface::class);
    }

    /**
     * @param int $id
     *
     * @return Version|null
     */
    public static function getById($id)
    {
        try {
            /**
             * @var self $version
             */
            $version = self::getModelFactory()->build(Version::class);
            $version->getDao()->getById($id);

            return $version;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * disables the versioning for the current process, this is useful for importers, ...
     * There are no new versions created, the read continues to operate normally
     *
     * @static
     */
    public static function disable()
    {
        self::$disabled = true;
    }

    /**
     * see @ self::disable()
     * just enabled the creation of versioning in the current process
     *
     * @static
     */
    public static function enable()
    {
        self::$disabled = false;
    }

    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return !self::$disabled;
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        $this->dispatchEvent(new VersionEvent($this), VersionEvents::PRE_SAVE);

        // check if versioning is disabled for this process
        if (self::$disabled) {
            return;
        }

        $isAsset = false;
        if (!$this->date) {
            $this->setDate(time());
        }

        // get stack trace, if enabled
        if ($this->getGenerateStackTrace()) {
            try {
                throw new \Exception('not a real exception ... ;-)');
            } catch (\Exception $e) {
                $this->stackTrace = $e->getTraceAsString();
            }
        }

        $data = $this->getData();

        // if necessary convert the data to save it to filesystem
        if (is_object($data) || is_array($data)) {
            // this is because of lazy loaded element inside documents and objects (eg: relational data-types, fieldcollections, ...)
            $fromRuntime = null;
            $cacheKey = null;
            if ($data instanceof Element\ElementInterface) {
                Element\Service::loadAllFields($data);
            }

            $this->setSerialized(true);

            $condensedData = $this->marshalData($data);

            $dataString = Serialize::serialize($condensedData);

            // revert all changed made by __sleep()
            if (method_exists($data, '__wakeup')) {
                $data->__wakeup();
            }
        } else {
            $dataString = $data;
        }

        if ($data instanceof Asset && $data->getType() != 'folder') {
            $isAsset = true;
            $dataStream = $data->getStream();
            $ctx = hash_init('sha3-512');
            hash_update_stream($ctx, $dataStream);
            $this->setBinaryFileHash(hash_final($ctx));
        }

        $this->setStorageType($this->storageAdapter->getStorageType(strlen($dataString),
            $isAsset ? $data->getfileSize() : null));

        if ($isAsset) {
            $this->setBinaryFileId($this->getDao()->getBinaryFileIdForHash($this->getBinaryFileHash()));
        }

        $id = $this->getDao()->save();
        $this->setId($id);

        $this->storageAdapter->save($this, $dataString, $isAsset ? $data->getStream() : null);

        $this->dispatchEvent(new VersionEvent($this), VersionEvents::POST_SAVE);
    }

    /**
     * @param ElementInterface $data
     *
     * @return mixed
     */
    private function marshalData($data)
    {
        $context = [
            'source' => __METHOD__,
            'conversion' => 'marshal',
            'defaultFilters' => true,
        ];

        $copier = Service::getDeepCopyInstance($data, $context);

        if ($data instanceof Concrete) {
            $copier->addFilter(
                new PimcoreClassDefinitionReplaceFilter(
                    function (Concrete $object, Data $fieldDefinition, $property, $currentValue) {
                        if ($fieldDefinition instanceof Data\CustomVersionMarshalInterface) {
                            return $fieldDefinition->marshalVersion($object, $currentValue);
                        }

                        return $currentValue;
                    }
                ),
                new PimcoreClassDefinitionMatcher(Data\CustomVersionMarshalInterface::class)
            );
        }

        $copier->addFilter(new SetDumpStateFilter(true), new \DeepCopy\Matcher\PropertyMatcher(ElementDumpStateInterface::class, ElementDumpStateInterface::DUMP_STATE_PROPERTY_NAME));
        $newData = $copier->copy($data);

        return $newData;
    }

    /**
     * @param ElementInterface $data
     *
     * @return mixed
     */
    private function unmarshalData($data)
    {
        $context = [
            'source' => __METHOD__,
            'conversion' => 'unmarshal',
            'defaultFilters' => false,
        ];
        $copier = Service::getDeepCopyInstance($data, $context);

        if ($data instanceof Concrete) {
            $copier->addFilter(
                new PimcoreClassDefinitionReplaceFilter(
                    function (Concrete $object, Data $fieldDefinition, $property, $currentValue) {
                        if ($fieldDefinition instanceof Data\CustomVersionMarshalInterface) {
                            return $fieldDefinition->unmarshalVersion($object, $currentValue);
                        }

                        return $currentValue;
                    }
                ),
                new PimcoreClassDefinitionMatcher(Data\CustomVersionMarshalInterface::class)
            );
        }

        return $copier->copy($data);
    }

    /**
     * Delete this Version
     */
    public function delete()
    {
        $this->dispatchEvent(new VersionEvent($this), VersionEvents::PRE_DELETE);

        $this->storageAdapter->delete($this,
            $this->getDao()->isBinaryHashInUse($this->getBinaryFileHash()));

        $this->getDao()->delete();
        $this->dispatchEvent(new VersionEvent($this), VersionEvents::POST_DELETE);
    }

    /**
     * @internal
     *
     * @param bool $renewReferences
     *
     * @return mixed
     */
    public function loadData($renewReferences = true)
    {
        $data = $this->storageAdapter->loadMetaData($this);

        if (!$data) {
            $msg = 'Version: cannot read version data with storage type: ' . $this->getStorageType();
            Logger::err($msg);

            throw new \Exception($msg);
        }

        if ($this->getSerialized()) {
            // this makes it possible to restore data object versions from older Pimcore versions
            @class_alias(GeoCoordinates::class, 'Pimcore\Model\DataObject\Data\Geopoint');

            $data = Serialize::unserialize($data);
            //clear runtime cache to avoid dealing with marshalled data
            \Pimcore::collectGarbage();
            if ($data instanceof \__PHP_Incomplete_Class) {
                Logger::err('Version: cannot read version data from file system because of incompatible class.');

                return null;
            }

            $data = $this->unmarshalData($data);
        }

        if ($data instanceof Concrete) {
            $data->markAllLazyLoadedKeysAsLoaded();
        }

        if ($data instanceof Asset) {
            $binaryStream = $this->storageAdapter->loadBinaryData($this);
            if ($binaryStream) {
                $data->setStream($binaryStream);
            }
        }

        if ($renewReferences) {
            $data = Element\Service::renewReferences($data);
        }

        $this->setData($data);

        return $data;
    }

    /**
     * @return mixed
     */
    public function getFileStream()
    {
        return $this->storageAdapter->getFileStream($this);
    }

    /**
     * @return mixed
     */
    public function getBinaryFileStream()
    {
        return $this->storageAdapter->getBinaryFileStream($this);
    }

    /**
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $cid
     *
     * @return $this
     */
    public function setCid($cid)
    {
        $this->cid = (int) $cid;

        return $this;
    }

    /**
     * @param int $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = (int) $date;

        return $this;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @param string $note
     *
     * @return $this
     */
    public function setNote($note)
    {
        $this->note = (string) $note;

        return $this;
    }

    /**
     * @param int $userId
     *
     * @return $this
     */
    public function setUserId($userId)
    {
        if (is_numeric($userId)) {
            if ($user = User::getById($userId)) {
                $this->userId = (int) $userId;
                $this->setUser($user);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        if (!$this->data) {
            $this->loadData();
        }

        return $this->data;
    }

    /**
     * @param mixed $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSerialized()
    {
        return $this->serialized;
    }

    /**
     * @param bool $serialized
     *
     * @return $this
     */
    public function setSerialized($serialized)
    {
        $this->serialized = (bool) $serialized;

        return $this;
    }

    /**
     * @return string
     */
    public function getCtype()
    {
        return $this->ctype;
    }

    /**
     * @param string $ctype
     *
     * @return $this
     */
    public function setCtype($ctype)
    {
        $this->ctype = (string) $ctype;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     *
     * @return $this
     */
    public function setUser(?User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * @param bool $public
     *
     * @return $this
     */
    public function setPublic($public)
    {
        $this->public = (bool) $public;

        return $this;
    }

    /**
     * @return int
     */
    public function getVersionCount(): int
    {
        return $this->versionCount ? $this->versionCount : 0;
    }

    /**
     * @param int $versionCount
     */
    public function setVersionCount($versionCount): void
    {
        $this->versionCount = (int) $versionCount;
    }

    /**
     * @return string|null
     */
    public function getBinaryFileHash(): ?string
    {
        return $this->binaryFileHash;
    }

    /**
     * @param string|null $binaryFileHash
     */
    public function setBinaryFileHash(?string $binaryFileHash): void
    {
        $this->binaryFileHash = $binaryFileHash;
    }

    /**
     * @return int|null
     */
    public function getBinaryFileId(): ?int
    {
        return $this->binaryFileId;
    }

    /**
     * @param int|null $binaryFileId
     */
    public function setBinaryFileId(?int $binaryFileId): void
    {
        $this->binaryFileId = $binaryFileId;
    }

    /**
     * @return bool
     */
    public function getGenerateStackTrace()
    {
        return (bool) $this->generateStackTrace;
    }

    /**
     * @param bool $generateStackTrace
     */
    public function setGenerateStackTrace(bool $generateStackTrace): void
    {
        $this->generateStackTrace = $generateStackTrace;
    }

    /**
     * @param string|null $stackTrace
     */
    public function setStackTrace(?string $stackTrace): void
    {
        $this->stackTrace = $stackTrace;
    }

    /**
     * @return string|null
     */
    public function getStackTrace(): ?string
    {
        return $this->stackTrace;
    }

    /**
     * @return bool
     */
    public function isAutoSave(): bool
    {
        return $this->autoSave;
    }

    /**
     * @param bool $autoSave
     */
    public function setAutoSave(bool $autoSave): self
    {
        $this->autoSave = $autoSave;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStorageType(): ?string
    {
        return $this->storageType;
    }

    /**
     * @param string $storageType
     */
    public function setStorageType(string $storageType): void
    {
        $this->storageType = $storageType;
    }
}
