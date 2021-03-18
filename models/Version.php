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
 * @package    Version
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

use Pimcore\Cache\Runtime;
use Pimcore\Event\Model\VersionEvent;
use Pimcore\Event\VersionEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionMatcher;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionReplaceFilter;
use Pimcore\Model\Element\ElementDumpStateInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Version\SetDumpStateFilter;
use Pimcore\Tool\Serialize;

/**
 * @method \Pimcore\Model\Version\Dao getDao()
 */
class Version extends AbstractModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $cid;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var int
     */
    public $userId;

    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $note;

    /**
     * @var int
     */
    public $date;

    /**
     * @var mixed
     */
    public $data;

    /**
     * @var bool
     */
    public $public = false;

    /**
     * @var bool
     */
    public $serialized = false;

    /**
     * @var string|null
     */
    public $stackTrace = '';

    /**
     * @var bool
     */
    protected $generateStackTrace = true;

    /**
     * @var int
     */
    public $versionCount = 0;

    /**
     * @var string|null
     */
    public $binaryFileHash;

    /**
     * @var int|null
     */
    public $binaryFileId;

    /**
     * @var bool
     */
    public static $disabled = false;

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
        \Pimcore::getEventDispatcher()->dispatch(VersionEvents::PRE_SAVE, new VersionEvent($this));

        // check if versioning is disabled for this process
        if (self::$disabled) {
            return;
        }

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
        if ($data instanceof Asset && $data->getType() != 'folder' && file_exists($data->getFileSystemPath())) {
            $isAssetFile = true;
            $this->binaryFileHash = hash_file('sha3-512', $data->getFileSystemPath());
            $this->binaryFileId = $this->getDao()->getBinaryFileIdForHash($this->binaryFileHash);
        }

        $id = $this->getDao()->save();
        $this->setId($id);

        // check if directory exists
        $saveDir = dirname($this->getFilePath());

        if (!is_dir($saveDir)) {
            File::mkdir($saveDir);
        }

        // save data to filesystem
        if (!is_writable(dirname($this->getFilePath())) || (is_file($this->getFilePath()) && !is_writable($this->getFilePath()))) {
            throw new \Exception('Cannot save version for element ' . $this->getCid() . ' with type ' . $this->getCtype() . ' because the file ' . $this->getFilePath() . ' is not writeable.');
        } else {
            File::put($this->getFilePath(), $dataString);

            // assets are kinda special because they can contain massive amount of binary data which isn't serialized, we append it to the data file
            if ($isAssetFile && !file_exists($this->getBinaryFilePath())) {
                $linked = false;

                // we always try to create a hardlink onto the original file, the asset ensures that not the actual
                // inodes get overwritten but creates new inodes if the content changes. This is done by deleting the
                // old file first before opening a new stream -> see Asset::update()
                $useHardlinks = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['versions']['use_hardlinks'];
                if ($useHardlinks && stream_is_local($this->getBinaryFilePath()) && stream_is_local($data->getFileSystemPath())) {
                    $linked = @link($data->getFileSystemPath(), $this->getBinaryFilePath());
                }

                if (!$linked) {
                    // append binary data to version file
                    $handle = fopen($this->getBinaryFilePath(), 'w', false, File::getContext());
                    $src = $data->getStream();
                    stream_copy_to_stream($src, $handle);
                    fclose($handle);
                }
            }
        }
        \Pimcore::getEventDispatcher()->dispatch(VersionEvents::POST_SAVE, new VersionEvent($this));
    }

    /**
     * @param ElementInterface $data
     *
     * @return mixed
     */
    public function marshalData($data)
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
    public function unmarshalData($data)
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
        \Pimcore::getEventDispatcher()->dispatch(VersionEvents::PRE_DELETE, new VersionEvent($this));

        foreach ([$this->getFilePath(), $this->getLegacyFilePath()] as $path) {
            if (is_file($path)) {
                @unlink($path);
            }

            $compressed = $path . '.gz';
            if (is_file($compressed)) {
                @unlink($compressed);
            }
        }

        if (is_file($this->getBinaryFilePath()) && !$this->getDao()->isBinaryHashInUse($this->getBinaryFileHash())) {
            @unlink($this->getBinaryFilePath());
        }

        $this->getDao()->delete();
        \Pimcore::getEventDispatcher()->dispatch(VersionEvents::POST_DELETE, new VersionEvent($this));
    }

    /**
     * Object
     *
     * @param bool $renewReferences
     *
     * @return mixed
     */
    public function loadData($renewReferences = true)
    {
        $data = null;
        $zipped = false;
        $filePath = null;

        // check both the legacy file path and the new structure
        foreach ([$this->getFilePath(), $this->getLegacyFilePath()] as $path) {
            if (file_exists($path)) {
                $filePath = $path;
                break;
            }

            if (file_exists($path . '.gz')) {
                $filePath = $path . '.gz';
                $zipped = true;
                break;
            }
        }

        if ($zipped && is_file($filePath) && is_readable($filePath)) {
            $data = gzdecode(file_get_contents($filePath));
        } elseif (is_file($filePath) && is_readable($filePath)) {
            $data = file_get_contents($filePath);
        }

        if (!$data) {
            Logger::err('Version: cannot read version data from file system.');
            $this->delete();

            return;
        }

        if ($this->getSerialized()) {
            $data = Serialize::unserialize($data);
            //clear runtime cache to avoid dealing with marshalled data
            Runtime::clear();
            if ($data instanceof \__PHP_Incomplete_Class) {
                Logger::err('Version: cannot read version data from file system because of incompatible class.');

                return;
            }

            $data = $this->unmarshalData($data);
        }

        if ($data instanceof Concrete) {
            $data->markAllLazyLoadedKeysAsLoaded();
        }

        if ($data instanceof Asset && file_exists($this->getBinaryFilePath())) {
            $binaryHandle = fopen($this->getBinaryFilePath(), 'rb', false, File::getContext());
            $data->setStream($binaryHandle);
        } elseif ($data instanceof Asset && $data->getObjectVar('data')) {
            // this is for backward compatibility
            $data->setData($data->getObjectVar('data'));
        }

        if ($renewReferences) {
            $data = Element\Service::renewReferences($data);
        }

        $this->setData($data);

        return $data;
    }

    /**
     * Returns the path on the file system
     *
     * @param int|null $id
     *
     * @return string
     */
    public function getFilePath(?int $id = null)
    {
        if (!$id) {
            $id = $this->getId();
        }

        $group = floor($this->getCid() / 10000) * 10000;
        $path = PIMCORE_VERSION_DIRECTORY . '/' . $this->getCtype() . '/g' . $group . '/' . $this->getCid() . '/' . $id;
        if (!is_dir(dirname($path))) {
            \Pimcore\File::mkdir(dirname($path));
        }

        return $path;
    }

    /**
     * @return string
     */
    public function getBinaryFilePath()
    {
        // compatibility
        $compatibilityPath = $this->getLegacyFilePath() . '.bin';
        if (file_exists($compatibilityPath)) {
            return $compatibilityPath;
        }

        return $this->getFilePath($this->binaryFileId) . '.bin';
    }

    /**
     * @return string
     */
    public function getLegacyFilePath()
    {
        return PIMCORE_VERSION_DIRECTORY . '/' . $this->getCtype() . '/' . $this->getId();
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
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
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
}
