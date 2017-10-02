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

use Pimcore\Config;
use Pimcore\Event\Model\VersionEvent;
use Pimcore\Event\VersionEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\Element\ElementInterface;
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
     * @var string
     */
    public $stackTrace = '';

    /**
     * @var bool
     */
    public static $disabled = false;

    /**
     * @param int $id
     *
     * @return Version
     */
    public static function getById($id)
    {
        $version = new self();
        $version->getDao()->getById($id);

        return $version;
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

        // get stack trace
        try {
            throw new \Exception('not a real exception ... ;-)');
        } catch (\Exception $e) {
            $this->stackTrace = $e->getTraceAsString();
        }

        $data = $this->getData();
        // if necessary convert the data to save it to filesystem
        if (is_object($data) or is_array($data)) {

            // this is because of lazy loaded element inside documents and objects (eg: multihref, objects, fieldcollections, ...)
            if ($data instanceof Element\ElementInterface) {
                Element\Service::loadAllFields($data);
            }

            $this->setSerialized(true);

            $data->_fulldump = true;
            $dataString = Serialize::serialize($data);

            // revert all changed made by __sleep()
            if (method_exists($data, '__wakeup')) {
                $data->__wakeup();
            }
            unset($data->_fulldump);
        } else {
            $dataString = $data;
        }

        $this->id = $this->getDao()->save();

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

            // assets are kina special because they can contain massive amount of binary data which isn't serialized, we append it to the data file
            if ($data instanceof Asset && $data->getType() != 'folder') {
                // append binary data to version file
                $handle = fopen($this->getBinaryFilePath(), 'w', false, File::getContext());
                $src = $data->getStream();
                stream_copy_to_stream($src, $handle);
                fclose($handle);
            }
        }
        \Pimcore::getEventDispatcher()->dispatch(VersionEvents::POST_SAVE, new VersionEvent($this));
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

        if (is_file($this->getBinaryFilePath())) {
            @unlink($this->getBinaryFilePath());
        }

        $this->getDao()->delete();
        \Pimcore::getEventDispatcher()->dispatch(VersionEvents::POST_DELETE, new VersionEvent($this));
    }

    /**
     * Object
     *
     * @return mixed
     */
    public function loadData()
    {
        $data = null;
        $zipped = false;

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
        }

        if ($data instanceof Asset && file_exists($this->getBinaryFilePath())) {
            $binaryHandle = fopen($this->getBinaryFilePath(), 'r+', false, File::getContext());
            $data->setStream($binaryHandle);
        } elseif ($data instanceof Asset && $data->data) {
            // this is for backward compatibility
            $data->setData($data->data);
        }

        $data = Element\Service::renewReferences($data);
        $this->setData($data);

        return $data;
    }

    /**
     * Returns the path on the file system
     *
     * @return string
     */
    protected function getFilePath()
    {
        $group = floor($this->getCid() / 10000) * 10000;
        $path = PIMCORE_VERSION_DIRECTORY . '/' . $this->getCtype() . '/g' . $group . '/' . $this->getCid() . '/' . $this->getId();
        if (!is_dir(dirname($path))) {
            \Pimcore\File::mkdir(dirname($path));
        }

        return $path;
    }

    /**
     * @return string
     */
    protected function getBinaryFilePath()
    {

        // compatibility
        $compatibilityPath = $this->getLegacyFilePath() . '.bin';
        if (file_exists($compatibilityPath)) {
            return $compatibilityPath;
        }

        return $this->getFilePath() . '.bin';
    }

    /**
     * @return string
     */
    protected function getLegacyFilePath()
    {
        return PIMCORE_VERSION_DIRECTORY . '/' . $this->getCtype() . '/' . $this->getId();
    }

    /**
     * the cleanup is now done in the maintenance see self::maintenanceCleanUp()
     *
     * @deprecated
     */
    public function cleanHistory()
    {
        if ($this->getCtype() == 'document') {
            $conf = Config::getSystemConfig()->documents->versions;
        } elseif ($this->getCtype() == 'asset') {
            $conf = Config::getSystemConfig()->assets->versions;
        } elseif ($this->getCtype() == 'object') {
            $conf = Config::getSystemConfig()->objects->versions;
        } else {
            return;
        }

        $days = [];
        $steps = [];

        if (intval($conf->days) > 0) {
            $days = $this->getDao()->getOutdatedVersionsDays($conf->days);
        } else {
            $steps = $this->getDao()->getOutdatedVersionsSteps(intval($conf->steps));
        }

        $versions = array_merge($days, $steps);

        foreach ($versions as $id) {
            $version = Version::getById($id);
            $version->delete();
        }
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
     * @param $cid
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

    public function maintenanceCompress()
    {
        $perIteration = 100;
        $alreadyCompressedCounter = 0;
        $overallCounter = 0;

        $list = new Version\Listing();
        $list->setCondition('date < ' . (time() - 86400 * 30));
        $list->setOrderKey('date');
        $list->setOrder('DESC');
        $list->setLimit($perIteration);

        $total = $list->getTotalCount();
        $iterations = ceil($total / $perIteration);

        for ($i=0; $i < $iterations; $i++) {
            Logger::debug('iteration ' . ($i + 1) . ' of ' . $iterations);

            $list->setOffset($i * $perIteration);

            $versions = $list->load();

            foreach ($versions as $version) {
                $overallCounter++;

                if (file_exists($version->getFilePath())) {
                    gzcompressfile($version->getFilePath(), 9);
                    @unlink($version->getFilePath());

                    $alreadyCompressedCounter = 0;

                    Logger::debug('version compressed:' . $version->getFilePath());
                    Logger::debug('Waiting 1 sec to not kill the server...');
                    sleep(1);
                } else {
                    $alreadyCompressedCounter++;
                }
            }

            \Pimcore::collectGarbage();

            // check here how many already compressed versions we've found so far, if over 100 skip here
            // this is necessary to keep the load on the system low
            // is would be very unusual that older versions are not already compressed, so we assume that only new
            // versions need to be compressed, that's not perfect but a compromise we can (hopefully) live with.
            if ($alreadyCompressedCounter > 100) {
                Logger::debug('Over ' . $alreadyCompressedCounter . " versions were already compressed before, it doesn't seem that there are still uncompressed versions in the past, skip...");

                return;
            }
        }
    }

    public function maintenanceCleanUp()
    {
        $conf['document'] = Config::getSystemConfig()->documents->versions;
        $conf['asset'] = Config::getSystemConfig()->assets->versions;
        $conf['object'] = Config::getSystemConfig()->objects->versions;

        $elementTypes = [];

        foreach ($conf as $elementType => $tConf) {
            if (intval($tConf->days) > 0) {
                $versioningType = 'days';
                $value = intval($tConf->days);
            } else {
                $versioningType = 'steps';
                $value = intval($tConf->steps);
            }

            if ($versioningType) {
                $elementTypes[] = [
                    'elementType' => $elementType,
                    $versioningType => $value
                ];
            }
        }

        $ignoredIds = [];

        while (true) {
            $versions = $this->getDao()->maintenanceGetOutdatedVersions($elementTypes, $ignoredIds);
            if (count($versions) == 0) {
                break;
            }
            $counter = 0;

            Logger::debug('versions to check: ' . count($versions));
            if (is_array($versions) && !empty($versions)) {
                $totalCount = count($versions);
                foreach ($versions as $index => $id) {
                    try {
                        $version = Version::getById($id);
                    } catch (\Exception $e) {
                        $ignoredIds[] = $id;
                        Logger::debug('Version with ' . $id . " not found\n");
                        continue;
                    }
                    $counter++;

                    // do not delete public versions
                    if ($version->getPublic()) {
                        $ignoredIds[] = $version->getId();
                        continue;
                    }

                    // do not delete versions referenced in the scheduler
                    if ($this->getDao()->isVersionUsedInScheduler($version)) {
                        $ignoredIds[] = $version->getId();
                        continue;
                    }

                    if ($version->getCtype() == 'document') {
                        $element = Document::getById($version->getCid());
                    } elseif ($version->getCtype() == 'asset') {
                        $element = Asset::getById($version->getCid());
                    } elseif ($version->getCtype() == 'object') {
                        $element = DataObject::getById($version->getCid());
                    }

                    if ($element instanceof ElementInterface) {
                        Logger::debug('currently checking Element-ID: ' . $element->getId() . ' Element-Type: ' . Element\Service::getElementType($element) . ' in cycle: ' . $counter . '/' . $totalCount);

                        if ($element->getModificationDate() >= $version->getDate()) {
                            // delete version if it is outdated
                            Logger::debug('delete version: ' . $version->getId() . ' because it is outdated');
                            $version->delete();
                        } else {
                            $ignoredIds[] = $version->getId();
                            Logger::debug('do not delete version (' . $version->getId() . ") because version's date is newer than the actual modification date of the element. Element-ID: " . $element->getId() . ' Element-Type: ' . Element\Service::getElementType($element));
                        }
                    } else {
                        // delete version if the corresponding element doesn't exist anymore
                        Logger::debug('delete version (' . $version->getId() . ") because the corresponding element doesn't exist anymore");
                        $version->delete();
                    }

                    // call the garbage collector if memory consumption is > 100MB
                    if (memory_get_usage() > 100000000) {
                        \Pimcore::collectGarbage();
                    }
                }
            }
        }
    }
}
