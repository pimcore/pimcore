<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Version
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tool\Serialize;
use Pimcore\Config;
use Pimcore\File; 

class Version extends AbstractModel {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $cid;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var integer
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
     * @var integer
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
     * @var boolean
     */
    public $serialized = false;

    /**
     * @var bool
     */
    public static $disabled = false;


    /**
     * @param integer $id
     * @return Version
     */
    public static function getById($id) {

        $version = new self();
        $version->getResource()->getById($id);

        return $version;
    }

    /**
     * disables the versioning for the current process, this is useful for importers, ...
     * There are no new versions created, the read continues to operate normally
     *
     * @static
     * @return void
     */
    public static function disable () {
        self::$disabled = true;
    }

    /**
     * see @ self::disable()
     * just enabled the creation of versioning in the current process
     *
     * @static
     * @return void
     */
    public static function enable () {
        self::$disabled = false;
    }


    /**
     * @return void
     */
    public function save() {

        // check if versioning is disabled for this process
        if(self::$disabled) {
            return;
        }

        if (!$this->date) {
            $this->setDate(time());
        }

        $data = $this->getData();
        // if necessary convert the data to save it to filesystem
        if (is_object($data) or is_array($data)) {

            // this is because of lazy loaded element inside documents and objects (eg: multihref, objects, fieldcollections, ...)
            if($data instanceof Element\ElementInterface) {
                Element\Service::loadAllFields($data);
            }

            $this->setSerialized(true);

            $data->_fulldump = true;
            $dataString = Serialize::serialize($data);

            // revert all changed made by __sleep()
            if(method_exists($data, "__wakeup")) {
                $data->__wakeup();
            }
            unset($data->_fulldump);

        } else {
            $dataString = $data;
        }

        $this->id = $this->getResource()->save();

        // check if directory exists
        $saveDir = dirname($this->getFilePath());
        if(!is_dir($saveDir)) {
            File::mkdir($saveDir);
        }

        // save data to filesystem
        if(!is_writable(dirname($this->getFilePath())) || (is_file($this->getFilePath()) && !is_writable($this->getFilePath()))) {
            throw new \Exception("Cannot save version for element " . $this->getCid() . " with type " . $this->getCtype() . " because the file " . $this->getFilePath() . " is not writeable.");
        } else {
            File::put($this->getFilePath(),$dataString);

            // assets are kina special because they can contain massive amount of binary data which isn't serialized, we append it to the data file
            if($data instanceof Asset && $data->getType() != "folder") {
                // append binary data to version file
                $handle = fopen($this->getBinaryFilePath(), "w+");
                $src = $data->getStream();
                stream_copy_to_stream($src, $handle);
                fclose($handle);
            }
        }
    }

    /**
     * @return void
     */
    public function delete() {

        foreach([$this->getFilePath(), $this->getLegacyFilePath()] as $path) {
            if(is_file($path)) {
                @unlink($path);
            }

            $compressed = $path . ".gz";
            if(is_file($compressed)) {
                @unlink($compressed);
            }
        }

        if(is_file($this->getBinaryFilePath())) {
            @unlink($this->getBinaryFilePath());
        }

        $this->getResource()->delete();
    }

    /**
     * Object
     *
     * @return mixed
     */
    public function loadData() {

        $data = null;
        $zipped = false;

        // check both the legacy file path and the new structure
        foreach([$this->getFilePath(), $this->getLegacyFilePath()] as $path) {
            if(file_exists($path)) {
                $filePath = $path;
                break;
            }

            if(file_exists($path . ".gz")) {
                $filePath = $path . ".gz";
                $zipped = true;
                break;
            }
        }

        if($zipped && is_file($filePath) && is_readable($filePath)){
            $data = gzdecode(file_get_contents($filePath));
        } else if(is_file($filePath) && is_readable($filePath)){
            $data = file_get_contents($filePath);
        }

        if(!$data) {
            \Logger::err("Version: cannot read version data from file system.");
            $this->delete();
            return;
        }

        if ($this->getSerialized()) {
            $data = Serialize::unserialize($data);
        }

        if($data instanceof Asset && file_exists($this->getBinaryFilePath())) {
            $binaryHandle = fopen($this->getBinaryFilePath(), "r+");
            $data->setStream($binaryHandle);
        } else if($data instanceof Asset && $data->data) {
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
    protected function getFilePath() {
        $group = floor($this->getCid() / 10000) * 10000;
        $path = PIMCORE_VERSION_DIRECTORY . "/" . $this->getCtype() . "/g" . $group . "/" . $this->getCid() . "/" . $this->getId();
        if(!is_dir(dirname($path))) {
            \Pimcore\File::mkdir(dirname($path));
        }

        return $path;
    }

    /**
     * @return string
     */
    protected function getBinaryFilePath() {

        // compatibility
        $compatibilityPath = $this->getLegacyFilePath() . ".bin";
        if(file_exists($compatibilityPath)) {
            return $compatibilityPath;
        }

        return $this->getFilePath() . ".bin";
    }

    /**
     * @return string
     */
    protected function getLegacyFilePath() {
        return PIMCORE_VERSION_DIRECTORY . "/" . $this->getCtype() . "/" . $this->getId();
    }

    /**
     * the cleanup is now done in the maintenance see self::maintenanceCleanUp()
     * @deprecated
     * @return void
     */
    public function cleanHistory() {
        if ($this->getCtype() == "document") {
            $conf = Config::getSystemConfig()->documents->versions;
        }
        else if ($this->getCtype() == "asset") {
            $conf = Config::getSystemConfig()->assets->versions;
        }
        else if ($this->getCtype() == "object") {
            $conf = Config::getSystemConfig()->objects->versions;
        }
        else {
            return;
        }

        $days = array();
        $steps = array();

        if (intval($conf->days) > 0) {
            $days = $this->getResource()->getOutdatedVersionsDays($conf->days);
        }
        else {
            $steps = $this->getResource()->getOutdatedVersionsSteps(intval($conf->steps));
        }

        $versions = array_merge($days, $steps);

        foreach ($versions as $id) {
            $version = Version::getById($id);
            $version->delete();
        }
    }

    /**
     * @return integer
     */
    public function getCid() {
        return $this->cid;
    }

    /**
     * @return integer
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNote() {
        return $this->note;
    }

    /**
     * @return integer
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @return void
     */
    public function setCid($cid) {
        $this->cid = (int) $cid;
        return $this;
    }

    /**
     * @param integer $date
     * @return void
     */
    public function setDate($date) {
        $this->date = (int) $date;
        return $this;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @param string $note
     * @return void
     */
    public function setNote($note) {
        $this->note = (string) $note;
        return $this;
    }

    /**
     * @param integer $userId
     * @return void
     */
    public function setUserId($userId) {

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
    public function getData() {
        if (!$this->data) {
            $this->loadData();
        }
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return void
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSerialized() {
        return $this->serialized;
    }

    /**
     * @param boolean $serialized
     * @return void
     */
    public function setSerialized($serialized) {
        $this->serialized = (bool) $serialized;
        return $this;
    }

    /**
     * @return string
     */
    public function getCtype() {
        return $this->ctype;
    }

    /**
     * @param string $ctype
     * @return void
     */
    public function setCtype($ctype) {
        $this->ctype = (string) $ctype;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param User $user
     * @return void
     */
    public function setUser($user) {
        $this->user = $user;
        return $this;
    }

    /**
     * @return bool
     */
    public function getPublic() {
        return $this->public;
    }
    
    /**
     * @return bool
     */
    public function isPublic() {
        return $this->public;
    }

    /**
     * @param bool $public
     * @return void
     */
    public function setPublic($public) {
        $this->public = (bool) $public;
        return $this;
    }


    public function maintenanceCompress() {

        $perIteration = 100;
        $alreadyCompressedCounter = 0;
        $overallCounter = 0;

        $list = new Version\Listing();
        $list->setCondition("date < " . (time() - 86400*30));
        $list->setOrderKey("date");
        $list->setOrder("DESC");
        $list->setLimit($perIteration);

        $total = $list->getTotalCount();
        $iterations = ceil($total/$perIteration);

        for($i=0; $i<$iterations; $i++) {

            \Logger::debug("iteration " . ($i+1) . " of " . $iterations);

            $list->setOffset($i*$perIteration);

            $versions = $list->load();

            foreach($versions as $version) {

                $overallCounter++;

                if(file_exists($version->getFilePath())) {
                    gzcompressfile($version->getFilePath(), 9);
                    @unlink($version->getFilePath());

                    $alreadyCompressedCounter = 0;

                    \Logger::debug("version compressed:" . $version->getFilePath());
                } else {
                    $alreadyCompressedCounter++;
                }

                if($overallCounter % 10 == 0) {
                    \Logger::debug("Waiting 5 secs to not kill the server...");
                    sleep(5);
                }
            }

            \Pimcore::collectGarbage();

            // check here how many already compressed versions we've found so far, if over 100 skip here
            // this is necessary to keep the load on the system low
            // is would be very unusual that older versions are not already compressed, so we assume that only new
            // versions need to be compressed, that's not perfect but a compromise we can (hopefully) live with.
            if($alreadyCompressedCounter > 100) {
                \Logger::debug("Over " . $alreadyCompressedCounter . " versions were already compressed before, it doesn't seem that there are still uncompressed versions in the past, skip...");
                return;
            }
        }
    }

    /**
     *
     */
    public function maintenanceCleanUp () {

        $conf["document"] = Config::getSystemConfig()->documents->versions;
        $conf["asset"] = Config::getSystemConfig()->assets->versions;
        $conf["object"] = Config::getSystemConfig()->objects->versions;

        $elementTypes = array();

        foreach ($conf as $elementType => $tConf) {
            if (intval($tConf->days) > 0) {
                $versioningType = "days";
                $value = intval($tConf->days);
            } else {
                $versioningType = "steps";
                $value = intval($tConf->steps);
            }

            if($versioningType) {
                $elementTypes[] = array(
                    "elementType" => $elementType,
                    $versioningType => $value
                );
            }
        }

        $ignoredIds = array();

        while (true) {
            $versions = $this->getResource()->maintenanceGetOutdatedVersions($elementTypes, $ignoredIds);
            if (count($versions) ==  0) {
                break;
            }
            $counter = 0;

            \Logger::debug("versions to check: " . count($versions));
            if(is_array($versions) && !empty($versions)) {
                $totalCount = count($versions);
                foreach ($versions as $index => $id) {
                    try {
                        $version = Version::getById($id);
                    } catch (\Exception $e) {
                        $ignoredIds[] = $id;
                        \Logger::debug("Version with " . $id . " not found\n");
                        continue;
                    }
                    $counter++;

                    // do not delete public versions
                    if($version->getPublic()) {
                        $ignoredIds[] = $version->getId();
                        continue;
                    }

                    if ($version->getCtype() == "document") {
                        $element = Document::getById($version->getCid());
                    }
                    else if ($version->getCtype() == "asset") {
                        $element = Asset::getById($version->getCid());
                    }
                    else if ($version->getCtype() == "object") {
                        $element = Object::getById($version->getCid());
                    }

                    if($element instanceof ElementInterface) {

                        \Logger::debug("currently checking Element-ID: " . $element->getId() . " Element-Type: " . Element\Service::getElementType($element) . " in cycle: " . $counter . "/" . $totalCount);

                        if($element->getModificationDate() >= $version->getDate()) {
                            // delete version if it is outdated
                            \Logger::debug("delete version: " . $version->getId() . " because it is outdated");
                            $version->delete();
                        } else {
                            $ignoredIds[] = $version->getId();
                            \Logger::debug("do not delete version (" . $version->getId() . ") because version's date is newer than the actual modification date of the element. Element-ID: " . $element->getId() . " Element-Type: " . Element\Service::getElementType($element));
                        }
                    } else {
                        // delete version if the corresponding element doesn't exist anymore
                        \Logger::debug("delete version (" . $version->getId() . ") because the corresponding element doesn't exist anymore");
                        $version->delete();
                    }

                    // call the garbage collector if memory consumption is > 100MB
                    if(memory_get_usage() > 100000000) {
                        \Pimcore::collectGarbage();
                    }
                }
            }
        }
    }
}
