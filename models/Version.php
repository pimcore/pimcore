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

namespace Pimcore\Model;

use __PHP_Incomplete_Class;
use Exception;
use Pimcore;
use Pimcore\Event\Model\VersionEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Event\VersionEvents;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
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

    protected ?int $id = null;

    protected int $cid;

    protected string $ctype;

    protected int $userId;

    protected ?User $user = null;

    protected string $note = '';

    protected int $date;

    protected mixed $data = null;

    protected bool $public = false;

    protected bool $serialized = false;

    protected ?string $stackTrace = null;

    protected bool $generateStackTrace = true;

    protected int $versionCount = 0;

    protected ?string $binaryFileHash = null;

    protected ?int $binaryFileId = null;

    public static bool $disabled = false;

    protected bool $autoSave = false;

    protected ?string $storageType = null;

    protected VersionStorageAdapterInterface $storageAdapter;

    public function __construct()
    {
        $this->storageAdapter = Pimcore::getContainer()->get(VersionStorageAdapterInterface::class);
    }

    public static function getById(int $id): ?Version
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
     */
    public static function disable(): void
    {
        self::$disabled = true;
    }

    /**
     * see @ self::disable()
     * just enabled the creation of versioning in the current process
     */
    public static function enable(): void
    {
        self::$disabled = false;
    }

    public static function isEnabled(): bool
    {
        return !self::$disabled;
    }

    public function save(): void
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
            $this->stackTrace = (new Exception())->getTraceAsString();
        }

        $data = $this->getData();

        // if necessary convert the data to save it to filesystem
        if (is_object($data) || is_array($data)) {
            // this is because of lazy loaded element inside documents and objects (eg: relational data-types, fieldcollections, ...)
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

    private function marshalData(ElementInterface $data): mixed
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

    private function unmarshalData(ElementInterface $data): mixed
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
    public function delete(): void
    {
        $this->dispatchEvent(new VersionEvent($this), VersionEvents::PRE_DELETE);

        $this->storageAdapter->delete($this,
            $this->getDao()->isBinaryHashInUse($this->getBinaryFileHash()));

        $this->getDao()->delete();
        $this->dispatchEvent(new VersionEvent($this), VersionEvents::POST_DELETE);
    }

    /**
     *
     *
     * @internal
     */
    public function loadData(bool $renewReferences = true): mixed
    {
        $data = $this->storageAdapter->loadMetaData($this);

        if (!$data) {
            $msg = 'Version: cannot read version data with storage type: ' . $this->getStorageType();
            Logger::err($msg);

            return null;
        }

        if ($this->getSerialized()) {
            $data = Serialize::unserialize($data);
            //clear runtime cache to avoid dealing with marshalled data
            Pimcore::collectGarbage();
            if ($data instanceof __PHP_Incomplete_Class) {
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

    public function getFileStream(): mixed
    {
        return $this->storageAdapter->getFileStream($this);
    }

    public function getBinaryFileStream(): mixed
    {
        return $this->storageAdapter->getBinaryFileStream($this);
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return $this
     */
    public function setCid(int $cid): static
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDate(int $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setNote(string $note): static
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUserId(int $userId): static
    {
        if ($user = User::getById($userId)) {
            $this->userId = $userId;
            $this->setUser($user);
        }

        return $this;
    }

    public function getData(): mixed
    {
        if (!$this->data) {
            $this->loadData();
        }

        return $this->data;
    }

    /**
     * @return $this
     */
    public function setData(mixed $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getSerialized(): bool
    {
        return $this->serialized;
    }

    /**
     * @return $this
     */
    public function setSerialized(bool $serialized): static
    {
        $this->serialized = $serialized;

        return $this;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    /**
     * @return $this
     */
    public function setCtype(string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPublic(): bool
    {
        return $this->public;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * @return $this
     */
    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getVersionCount(): int
    {
        return $this->versionCount ?: 0;
    }

    public function setVersionCount(int $versionCount): void
    {
        $this->versionCount = $versionCount;
    }

    public function getBinaryFileHash(): ?string
    {
        return $this->binaryFileHash;
    }

    public function setBinaryFileHash(?string $binaryFileHash): void
    {
        $this->binaryFileHash = $binaryFileHash;
    }

    public function getBinaryFileId(): ?int
    {
        return $this->binaryFileId;
    }

    public function setBinaryFileId(?int $binaryFileId): void
    {
        $this->binaryFileId = $binaryFileId;
    }

    public function getGenerateStackTrace(): bool
    {
        return $this->generateStackTrace;
    }

    public function setGenerateStackTrace(bool $generateStackTrace): void
    {
        $this->generateStackTrace = $generateStackTrace;
    }

    public function setStackTrace(?string $stackTrace): void
    {
        $this->stackTrace = $stackTrace;
    }

    public function getStackTrace(): ?string
    {
        return $this->stackTrace;
    }

    public function isAutoSave(): bool
    {
        return $this->autoSave;
    }

    /**
     * @return $this
     */
    public function setAutoSave(bool $autoSave): static
    {
        $this->autoSave = $autoSave;

        return $this;
    }

    public function getStorageType(): ?string
    {
        return $this->storageType;
    }

    public function setStorageType(string $storageType): void
    {
        $this->storageType = $storageType;
    }
}
