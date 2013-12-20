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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Version extends Pimcore_Model_Abstract {

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
            if($data instanceof Element_Interface) {
                Element_Service::loadAllFields($data);
            }

            $this->setSerialized(true);

            $data->_fulldump = true;
            $dataString = Pimcore_Tool_Serialize::serialize($data);

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
            Pimcore_File::mkdir($saveDir);
        }

        // save data to filesystem
        if(!is_writable(dirname($this->getFilePath())) || (is_file($this->getFilePath()) && !is_writable($this->getFilePath()))) {
            throw new Exception("Cannot save version for element " . $this->getCid() . " with type " . $this->getCtype() . " because the file " . $this->getFilePath() . " is not writeable.");
        } else {
            Pimcore_File::put($this->getFilePath(),$dataString);

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

        if(is_file($this->getFilePath())) {
            @unlink($this->getFilePath());
        }

        $this->getResource()->delete();
    }

    /**
     * Object
     *
     * @return mixed
     */
    public function loadData() {

        if(!is_file($this->getFilePath()) or !is_readable($this->getFilePath())){
            Logger::err("Version: cannot read version data from file system.");
            $this->delete();
            return;
        }

        $data = file_get_contents($this->getFilePath());

        if ($this->getSerialized()) {
            $data = Pimcore_Tool_Serialize::unserialize($data);
        }

        if($data instanceof Asset && file_exists($this->getBinaryFilePath())) {
            $binaryHandle = fopen($this->getBinaryFilePath(), "r+");
            $data->setStream($binaryHandle);
        } else if($data instanceof Asset && $data->data) {
            // this is for backward compatibility
            $data->setData($data->data);
        }

        $data = Element_Service::renewReferences($data);
        $this->setData($data);

        return $data;
    }
    

    /**
     * Returns the path on the file system
     *
     * @return string
     */
    protected function getFilePath() {
        return PIMCORE_VERSION_DIRECTORY . "/" . $this->getCtype() . "/" . $this->getId();
    }

    protected function getBinaryFilePath() {
        return $this->getFilePath() . ".bin";
    }

    /**
     * the cleanup is now done in the maintenance see self::maintenanceCleanUp()
     * @deprecated
     * @return void
     */
    public function cleanHistory() {
        if ($this->getCtype() == "document") {
            $conf = Pimcore_Config::getSystemConfig()->documents->versions;
        }
        else if ($this->getCtype() == "asset") {
            $conf = Pimcore_Config::getSystemConfig()->assets->versions;
        }
        else if ($this->getCtype() == "object") {
            $conf = Pimcore_Config::getSystemConfig()->objects->versions;
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
    

    /**
     *
     */
    public function maintenanceCleanUp () {

        $conf["document"] = Pimcore_Config::getSystemConfig()->documents->versions;
        $conf["asset"] = Pimcore_Config::getSystemConfig()->assets->versions;
        $conf["object"] = Pimcore_Config::getSystemConfig()->objects->versions;

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

            Logger::debug("versions to check: " . count($versions));
            if(is_array($versions) && !empty($versions)) {
                $totalCount = count($versions);
                foreach ($versions as $index => $id) {
                    try {
                        $version = Version::getById($id);
                    } catch (Exception $e) {
                        $ignoredIds[] = $id;
                        Logger::debug("Version with " . $id . " not found\n");
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
                        $element = Object_Abstract::getById($version->getCid());
                    }

                    if($element instanceof Element_Interface) {

                        Logger::debug("currently checking Element-ID: " . $element->getId() . " Element-Type: " . Element_Service::getElementType($element) . " in cycle: " . $counter . "/" . $totalCount);

                        if($element->getModificationDate() >= $version->getDate()) {
                            // delete version if it is outdated
                            Logger::debug("delete version: " . $version->getId() . " because it is outdated");
                            $version->delete();
                        } else {
                            $ignoredIds[] = $version->getId();
                            Logger::debug("do not delete version (" . $version->getId() . ") because version's date is newer than the actual modification date of the element. Element-ID: " . $element->getId() . " Element-Type: " . Element_Service::getElementType($element));
                        }
                    } else {
                        // delete version if the corresponding element doesn't exist anymore
                        Logger::debug("delete version (" . $version->getId() . ") because the corresponding element doesn't exist anymore");
                        $version->delete();
                    }

                    // call the garbage collector if memory consumption is > 100MB
                    if(memory_get_usage() > 100000000) {
                        Pimcore::collectGarbage();
                    }
                }
            }
        }
    }
}
