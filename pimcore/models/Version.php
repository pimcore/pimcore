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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
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
            $this->setSerialized(true);
            $data->_fulldump = true;
            $dataString = Pimcore_Tool_Serialize::serialize($this->getData());
            unset($this->_fulldump);
        } else {
            $dataString = $data;
        }

        $this->id = $this->getResource()->save();

        // check if directory exists
        $saveDir = dirname($this->getFilePath());
        if(!is_dir($saveDir)) {
            mkdir($saveDir, 0766, true);
        }

        // save data to filesystem
        if(!is_writable(dirname($this->getFilePath())) || (is_file($this->getFilePath()) && !is_writable($this->getFilePath()))) {
            throw new Exception("Cannot save version for element " . $this->getCid() . " with type " . $this->getCtype() . " because the file " . $this->getFilePath() . " is not writeable.");
        } else {
            file_put_contents($this->getFilePath(),$dataString);
        }

        $this->cleanHistory();
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

    /**
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
    }

    /**
     * @param integer $date
     * @return void
     */
    public function setDate($date) {
        $this->date = (int) $date;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
    }

    /**
     * @param string $note
     * @return void
     */
    public function setNote($note) {
        $this->note = (string) $note;
    }

    /**
     * @param integer $userId
     * @return void
     */
    public function setUserId($userId) {

        if (is_numeric($userId)) {
            if ($user = User::getById($userId)) {
                $this->userId = $userId;
                $this->setUser($user);
            }
        }
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
    }
    
    
    
    
    
    public function maintenanceCleanUp () {
        
        $conf["document"] = Pimcore_Config::getSystemConfig()->documents->versions;
        $conf["asset"] = Pimcore_Config::getSystemConfig()->assets->versions;
        $conf["object"] = Pimcore_Config::getSystemConfig()->objects->versions;
        
        $types = array();
        
        foreach ($conf as $type => $tConf) {
            if (intval($tConf->days) > 0) {
                $types[] = array(
                    "type" => $type,
                    "days" => intval($tConf->days)
                );
            }
        }        
        
        $versions = $this->getResource()->maintenanceGetOutdatedVersions($types);

        if(is_array($versions)) {
            foreach ($versions as $id) {
                $version = Version::getById($id);

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
                    if($element->getModificationDate() > $version->getDate()) {
                        // delete version if it is outdated
                        $version->delete();
                    }
                } else {
                    // delete version if the correspondening element doesn't exist anymore
                    $version->delete();
                }
            }
        }
    }
}
