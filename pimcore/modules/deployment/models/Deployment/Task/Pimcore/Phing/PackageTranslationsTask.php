<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 14.05.13
 * Time: 22:38
 */
class Deployment_Task_Pimcore_Phing_PackageTranslationsTask extends Deployment_Task_Pimcore_Phing_AbstractPackageTask {

    protected function getType(){
        return 'translations';
    }

    protected function getSubType(){
        return $this->getParam('type');
    }

    public function createPackage(){
        $type = $this->checkType($this->getParam('type'));
        $params = array_filter($this->getParams()); //remove empty keys -> could cause problems
        $data = $this->getWebserviceService()->getTranslations($type,$params);
        if(empty($data)){
            throw new BuildException("No Translations found.");
        }else{
            $this->log("Adding ". count($data) ." translations to package.",Project::MSG_DEBUG);
        }

        $dataString = $this->getWebserviceEncoder()->encode(array('success' => true,'data' => $data),true);

        $temporaryTaskDirectory = $this->getTemporaryTaskDirectory();
        $dataFile = $temporaryTaskDirectory . self::PACKAGE_DATA_FILE_NAME;

        if(file_put_contents($dataFile,$dataString,LOCK_EX) === false){
            throw new BuildException("Couldn't write data file: " . $dataFile);
        }else{
            $this->log("Created data file:\n\t" . $dataFile,Project::MSG_DEBUG);
        }

        $metaData = $this->getParams();
        $metaData['version'] = $this->getPackageDatabaseEntry()->getVersion();

        $this->log("Creating Phar archive:\n\t" . $this->getArchiveFile(). "\n with metaData:\n\t" . var_export($metaData,true),Project::MSG_DEBUG);
        Pimcore_Tool_Archive::createPhar($temporaryTaskDirectory,$this->getArchiveFile(),array(),array('metaData' => $metaData));
        recursiveDelete($temporaryTaskDirectory);

        $this->log("Created package:\n\t" . $this->getArchiveFile(),Project::MSG_INFO);
    }

    public function createBackup() {
        $this->log("Creating backup");
        $metaData = $this->getPharMetaData();
        $type = $this->checkType($metaData['type']);
        $resourceClass = 'Translation_'.ucfirst($type) . '_Resource';

        $options = array();
        $options['mysql-tables'] = array($resourceClass::getTableName());
        $options['only-mysql-related-tasks'] = null;
        $this->createTaskBackup($options);
        $this->log("Backup successfully created");
    }

    public function installPackage(){
        $phar = $this->getPharArchive();
        $metaData = $this->getPharMetaData();

        $data = $this->getPackageData();
        if($data['success']){
            foreach($data['data'] as $entry){
                $className = 'Translation_' . ucfirst($metaData['type']);
                $translation = new $className();
                $translation->getFromWebserviceImport($entry);
                $translation->save();
                $this->log("Added translation '". $translation->getKey()."'",Project::MSG_INFO);
            }
            Translation_Abstract::clearDependentCache();
        }else{
            throw new BuildException("Couldn't get Package data");
        }
    }

    public function rollback(){
        $this->restorePimcoreBackup($this->getBackupFilePath());
    }

    protected function checkType($type){
        if(!in_array($type, array('website','admin'))){
            throw new BuildException("'type' has to be 'website' or 'admin'.");
        }else{
            return $type;
        }
    }
}