<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 10.05.13
 * Time: 08:11
 */

abstract class Deployment_Task_Pimcore_Phing_AbstractPackageTask extends Deployment_Task_Pimcore_Phing_AbstractTask {

    const PACKAGE_DATA_FILE_NAME = 'package.data';
    const PACKAGE_PHAR_ARCHIVE_FILE_NAME = 'package.phar';
    const PACKAGE_BACKUP_FILE_NAME = 'backup.tar';

    protected $packageDatabaseEntry = null;

    protected function getPackageDatabaseEntry(){
        if(!$this->packageDatabaseEntry){
            $package = new Deployment_Package();
            $package->setType($this->getType());
            $package->setSubType($this->getSubType());
            $package->save();
            $this->packageDatabaseEntry = $package;
            $this->log("Create package database entry with ID " . $package->getId(),Project::MSG_DEBUG);
        }
        return $this->packageDatabaseEntry;
    }

    protected function getSubType(){
        return '';
    }

    protected function getPackageId(){
        if($this->getParam('packageId')){
            $packageId = $this->getParam('packageId');
        }else{
            $dbEntry = $this->getPackageDatabaseEntry();
            if(!$dbEntry->getId()){
                throw new BuildException("No packageId given!");
            }
            $packageId = $dbEntry->getId();
        }
        $this->project->setProperty('packageId',$packageId);
        return $packageId;
    }

    protected function getTemporaryTaskDirectory(){
        $directory = PIMCORE_TEMPORARY_DIRECTORY .'/deployment/' . $this->getPackageId() . '/';
        recursiveDelete($directory);

        $this->log("Creating temporary task directory:\n\t" . $directory,Project::MSG_DEBUG);
        @mkdir($directory,0755,true);
        return $directory;
    }

    protected function getPackageDirectory(){
        $directory = PIMCORE_DEPLOYMENT_PACKAGES_DIRECTORY . '/' . $this->getPackageId() . '/';
        @mkdir($directory,0755,true);
        return $directory;
    }

    protected function getArchiveFile(){
        return $this->getPackageDirectory() . static::PACKAGE_PHAR_ARCHIVE_FILE_NAME;
    }


    public function getPharArchive(){
        $file = $this->getPackageDirectory() . static::PACKAGE_PHAR_ARCHIVE_FILE_NAME;
        if(!is_readable($file)){
            throw new BuildException("Couldn't find data file: " . $file);
        }else{
            $this->log("Received Phar archive:\n\t" . $file,Project::MSG_DEBUG);
        }

        return new Phar($file);
    }

    protected function getPharMetaData(){
        $phar = $this->getPharArchive();
        $metaData = $phar->getMetadata();
        $this->log("Received Phar meta data :\n\t" . var_export($metaData,true),Project::MSG_DEBUG);
        return $metaData;
    }

    public function getPackageData(){
        $phar = $this->getPharArchive();
        $contentString = file_get_contents($phar[static::PACKAGE_DATA_FILE_NAME]->getPathName());
        $data = $this->getWebserviceEncoder()->decode($contentString);
        return $data;
    }

    public function main(){
        $deploymentAction = $this->getParam('deploymentAction');
        if($deploymentAction){
            if($deploymentAction == 'installPackage'){
                if($this->getPimcoreParam('remote')){
                    $params = $this->getPimcoreParams();
                    $params['packageId'] = $this->project->getProperty('packageId');
                    $params['target'] = $this->getCurrentDeploymentExecutionTarget()->getName();
                    unset($params['remote']);
                    foreach($this->getDeploymentInstances() as $instance){
                        $client = $instance->getRestClient();
                        $result = $client->deploymentExecuteTargetAction($params);
                        if($result->success){
                            $this->log("Remote command successfully (instance: " . $instance->getIdentifier().")",Project::MSG_INFO);
                            $this->log("Remote command: " . $result->data ,Project::MSG_DEBUG);
                        }else{
                            $this->log("Remote command: " . $result->data ,Project::MSG_DEBUG);
                            throw new BuildException("Remote command failed (instance: " . $instance->getIdentifier().")");
                        }
                    }
                }else{
                    $this->log("deploymentAction is '$deploymentAction' -> executing 'createBackup' before installing.",Project::MSG_INFO);
                    $this->createBackup();
                    try{
                        $this->log("Installing Package");
                        $this->$deploymentAction();
                        $this->log("Package successfully installed");
                    }catch(Exception $e){
                        $this->log("Package installation failed with error: \n\n" . $e ,"\n\n permforming 'rollback'",Project::MSG_INFO);
                        try{
                            $this->log("Starting rollback.");
                            $this->rollback();
                            $this->log("Rollback finished successfully :-)");
                        }catch (Exception $e){
                            throw new BuildException("\n\n--------------------\nEMERGENCY - ROLLBACK FAILED WITH ERRROR\n--------------------\n\n");
                        }
                    }
                }

            }else{
                $this->log("Executing deploymentAction '$deploymentAction'.",Project::MSG_INFO);
                if(!method_exists($this,$deploymentAction)){
                    throw new BuildException("deploymentAction '$deploymentAction' not defined!");
                }
                $this->$deploymentAction();
            }
        }else{
            $this->log("No deploymentAction -> executing 'main' action.",Project::MSG_INFO);
        }
    }

    protected function getBackupFilePath(){
        $backupFile = $this->getPackageDirectory() . self::PACKAGE_BACKUP_FILE_NAME;
        if(!is_readable($backupFile)){
            throw new BuildException("Could not get Backup file.");
        }else{
            $this->log("Received backup file path:\n\t" . $backupFile,Project::MSG_DEBUG);
            return $backupFile;
        }
    }

    protected function createTaskBackup($options){

        $options['directory'] = substr($this->getPackageDirectory(),0,strlen($this->getPackageDirectory())-1);
        $options['filename'] = str_replace('.tar','',self::PACKAGE_BACKUP_FILE_NAME); //backup script adds .tar automatically
        $options['overwrite'] = 'true';
        $options['verbose'] = null;
        $optionString = Pimcore_Tool_Console::getOptionString($options);
        $cmd = Pimcore_Tool_Console::getPhpCli() . ' ' . PIMCORE_DOCUMENT_ROOT .'/pimcore/cli/backup.php ' . $optionString;
        $this->log("Executing backup command:\n\t" . $cmd,Project::MSG_DEBUG);
        $result = shell_exec($cmd);
        if(is_null($result)){
            throw new BuildException("Backup failed - it seems that the Backup command couldn't be executed.");
        }

        if(!preg_match("/(backup finished)/is",$result)){
            $this->log("Backup execution result:\n\t" . $result,Project::MSG_ERR);
            throw new BuildException("Backup failed - it seems that an error occurred.");
        }
        return $this->getBackupFilePath(); //to ensure that the Backup file exists
    }

    protected function restorePimcoreBackup($backupFile){
        $options = array();
        $options['backup-file'] = $backupFile;
        $options['verbose'] = null;
        $optionString = Pimcore_Tool_Console::getOptionString($options);

        $cmd = Pimcore_Tool_Console::getPhpCli() . ' ' . PIMCORE_DOCUMENT_ROOT .'/pimcore/cli/backup-restore.php ' . $optionString;
        $this->log("Executing backup restore command:\n\t" . $cmd,Project::MSG_DEBUG);
        $result = shell_exec($cmd);

        if(is_null($result)){
            throw new BuildException("Backup restore failed - it seems that the backup restore command couldn't be executed.");
        }
        if(!preg_match("/(backup restore finished)/is",$result)){
            $this->log("Backup restore execution result:\n\t" . $result,Project::MSG_ERR);
            throw new BuildException("Backup failed - it seems that an error occurred.");
        }

        return true;
    }


    public abstract function createPackage();
    public abstract function createBackup();
    public abstract function rollback();
    protected abstract function getType();
    /*

    public abstract function installPackage();

    public abstract function restoreBackup();
*/
}

