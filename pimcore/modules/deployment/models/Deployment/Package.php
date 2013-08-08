<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 30.05.13
 * Time: 14:13
 */

class Deployment_Package extends Pimcore_Model_Abstract {

    public $id;
    public $type;
    public $subType;
    public $creationDate;
    public $version;

    /**
     * @param mixed $subType
     */
    public function setSubType($subType)
    {
        $this->subType = $subType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubType()
    {
        return $this->subType;
    }


    /**
     * @param mixed $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    public static function getById($id)
    {
        $id = intval($id);
        if ($id < 1) {
            return null;
        }

        try{
            $object = new Deployment_Package();
            $object->getResource()->getById($id);
            return $object;
        }catch (Exception $e){
            Logger::warn($e->getMessage());
        }
    }

    public function getForWebserviceExport(){
        $data = array();

        foreach(get_object_vars($this) as $key => $value){
            $data[$key] = $value;
        }
        unset($data['resource']);
        $data['packageDirectory'] = $this->getPackageDirectory();
        $data['packageDirectoryWebsitePath'] = str_replace(PIMCORE_DOCUMENT_ROOT,'',$data['packageDirectory']);
        $data['pharFile'] = $this->getPackageDirectory() . Deployment_Task_Pimcore_Phing_AbstractPackageTask::PACKAGE_PHAR_ARCHIVE_FILE_NAME;
        $data['pharFileWebsitePath'] = str_replace(PIMCORE_DOCUMENT_ROOT,'',$data['pharFile']);

        $data['checksum'] = md5_file($data['pharFile']);
        return $data;
    }

    public function getPackageDirectory(){
        if($this->getId()){
            return PIMCORE_DEPLOYMENT_PACKAGES_DIRECTORY.'/' .$this->getId() .'/';
        }else{
            throw new Exception("Package has no ID.");
        }
    }

}