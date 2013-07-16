<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 13.07.13
 * Time: 09:19
 */

class Deployment_Setup {

    public function run(){
        if(!is_dir(PIMCORE_DEPLOYMENT_DIRECTORY)){
            mkdir(PIMCORE_DEPLOYMENT_DIRECTORY,0755,true);
        }

        $revisionFileCheck = PIMCORE_DEPLOYMENT_DIRECTORY .'/.lastPimcoreRevisionCheck';
        if(!is_readable($revisionFileCheck)){
            $lastRevisionUpdate = 0;
        }else{
            $lastRevisionUpdate = (int)file_get_contents($revisionFileCheck);
            if($lastRevisionUpdate == Pimcore_Version::getRevision()){ //revision updates already run
                return;
            }
        }

        for($i = $lastRevisionUpdate; $i <= Pimcore_Version::getRevision();$i++){
            $updateMethod = 'updateRevision'.$i;
            if(method_exists($this,$updateMethod)){
                $this->$updateMethod();
            }
        }
        file_put_contents($revisionFileCheck,Pimcore_Version::getRevision());
    }

    public function updateRevision2808(){
        User_Permission_Definition::create('deployment');
        $this->createDatabaseTables();
    }

    protected function createDatabaseTables(){
        $db = Pimcore_Resource::get();
        $db->query("CREATE TABLE IF NOT EXISTS `deployment_packages` (
                        `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                        `type` VARCHAR(50) NOT NULL,
                        `subType` VARCHAR(50) NOT NULL,
                        `creationDate` BIGINT(20) NOT NULL,
                        `version` BIGINT(20) NOT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $db->query("CREATE TABLE IF NOT EXISTS `deployment_target` (
                        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `parentId` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
                        `name` VARCHAR(255) NOT NULL,
                        `creationDate` BIGINT(20) UNSIGNED NOT NULL,
                        `status` VARCHAR(50) NOT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    }

}