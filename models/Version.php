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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

use Pimcore\Cache\Runtime;
use Pimcore\Event\Model\VersionEvent;
use Pimcore\Event\VersionEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionMatcher;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionReplaceFilter;
use Pimcore\Model\Element\ElementDumpStateInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Version\SetDumpStateFilter;
use Pimcore\Tool\Serialize;
use Pimcore\Tool\Storage;

/**
 * @method \Pimcore\Model\Version\Dao getDao()
 */
final class Version extends AbstractModel
{
    /**
     * @var int
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
        } catch (\Exception $e) {
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
     * @throws \Exception
     */
    public function save()
    {
        \Pimcore::getEventDispatcher()->dispatch(new VersionEvent($this), VersionEvents::PRE_SAVE);

        // check if versioning is disabled for this process
        if (self::$disabled) {
            return;
        }

        $storage = Storage::get('version');

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
        if (is_object($data) or is_array($data)) {

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

        $isAssetFile = false;
        if ($data instanceof Asset && $data->getType() != 'folder') {
            $isAssetFile = true;

            $ctx = hash_init('sha3-512');
            hash_update_stream($ctx, $data->getStream());
            $this->binaryFileHash = hash_final($ctx);

            $this->binaryFileId = $this->getDao()->getBinaryFileIdForHash($this->binaryFileHash);
        }

        $id = $this->getDao()->save();
        $this->setId($id);

        $storage->write($this->getStoragePath(), $dataString);

        // assets are kinda special because they can contain massive amount of binary data which isn't serialized, we append it to the data file
        if ($isAssetFile && !$storage->fileExists($this->getBinaryStoragePath())) {
            $linked = false;

            // we always try to create a hardlink onto the original file, the asset ensures that not the actual
            // inodes get overwritten but creates new inodes if the content changes. This is done by deleting the
            // old file first before opening a new stream -> see Asset::update()
            $useHardlinks = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['versions']['use_hardlinks'];
            $storage->write($this->getBinaryStoragePath(), '1'); // temp file to determine if stream is local or not
            if ($useHardlinks && stream_is_local($this->getBinaryFileStream()) && stream_is_local($data->getStream())) {
                $linkPath = stream_get_meta_data($this->getBinaryFileStream())['uri'];
                $storage->delete($this->getBinaryStoragePath());
                $linked = @link(stream_get_meta_data($data->getStream())['uri'], $linkPath);
            }

            if (!$linked) {
                $storage->writeStream($this->getBinaryStoragePath(), $data->getStream());
            }
        }

        \Pimcore::getEventDispatcher()->dispatch(new VersionEvent($this), VersionEvents::POST_SAVE);
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
        \Pimcore::getEventDispatcher()->dispatch(new VersionEvent($this), VersionEvents::PRE_DELETE);

        $storage = Storage::get('version');

        if ($storage->fileExists($this->getStoragePath())) {
            $storage->delete($this->getStoragePath());
        }

        if ($storage->fileExists($this->getBinaryStoragePath()) && !$this->getDao()->isBinaryHashInUse($this->getBinaryFileHash())) {
            $storage->delete($this->getBinaryStoragePath());
        }

        $this->getDao()->delete();
        \Pimcore::getEventDispatcher()->dispatch(new VersionEvent($this), VersionEvents::POST_DELETE);
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
        $storage = Storage::get('version');
        $data = $storage->read($this->getStoragePath());

        if (!$data) {
            Logger::err('Version: cannot read version data from file system.');
            $this->delete();

            return null;
        }

        if ($this->getSerialized()) {
            // this makes it possible to restore data object versions from older Pimcore versions
            @class_alias(GeoCoordinates::class, 'Pimcore\Model\DataObject\Data\Geopoint');

            $data = Serialize::unserialize($data);
            //clear runtime cache to avoid dealing with marshalled data
            Runtime::clear();
            if ($data instanceof \__PHP_Incomplete_Class) {
                Logger::err('Version: cannot read version data from file system because of incompatible class.');

                return null;
            }

            $data = $this->unmarshalData($data);
        }

        if ($data instanceof Concrete) {
            $data->markAllLazyLoadedKeysAsLoaded();
        }

        if ($data instanceof Asset && $storage->fileExists($this->getBinaryStoragePath())) {
            $data->setStream($this->getBinaryFileStream());
        }

        if ($renewReferences) {
            $data = Element\Service::renewReferences($data);
        }

        $this->setData($data);

        return $data;
    }

    /**
     * @param int|null $id
     *
     * @return string
     */
    private function getStoragePath(?int $id = null): string
    {
        if (!$id) {
            $id = $this->getId();
        }

        $group = floor($this->getCid() / 10000) * 10000;

        return $this->getCtype() . '/g' . $group . '/' . $this->getCid() . '/' . $id;
    }

    /**
     * @return resource
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function getFileStream()
    {
        return Storage::get('version')->readStream($this->getStoragePath());
    }

    /**
     * @return string
     */
    private function getBinaryStoragePath(): string
    {
        return $this->getStoragePath($this->getBinaryFileId()) . '.bin';
    }

    /**
     * @return resource
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function getBinaryFileStream()
    {
        return Storage::get('version')->readStream($this->getBinaryStoragePath());
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
     * @return int
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
     * @return string
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
}
