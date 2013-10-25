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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Tool_Archive {

    /**
     * @param $sourceDir Source directory
     * @param $destinationFile destination zip file
     * @param array $excludeFilePattern exclude files
     * @param array $options
     * @return bool
     * @throws Exception
     */
    public static function createZip($sourceDir,$destinationFile,$excludeFilePattern = array(), $options = array()){
        list($sourceDir,$destinationFile,$items) = self::prepareArchive($sourceDir,$destinationFile);
        $mode = $options['mode'] ? $options['mode'] : ZIPARCHIVE::OVERWRITE;

        if(substr($sourceDir,-1,1) != DIRECTORY_SEPARATOR){
            $sourceDir .= DIRECTORY_SEPARATOR;
        }

        if(is_dir($sourceDir) && is_readable($sourceDir)){
            $items = rscandir($sourceDir);
        }else{
            throw new Exception("$sourceDir doesn't exits or is not readable!");
        }

        if(!$destinationFile || !is_string($destinationFile)){
            throw new Exception('No destinationFile provided!');
        }else{
            @unlink($destinationFile);
        }

        $destinationDir = dirname($destinationFile);
        if(!is_dir($destinationDir)){
            Pimcore_File::mkdir($destinationDir);
        }

        $zip = new ZipArchive();
        $zip->open($destinationFile, $mode);
        foreach($items as $item){
            $zipPath = str_replace($sourceDir,'',$item);

            foreach($excludeFilePattern as $excludePattern){
                if(preg_match($excludePattern,$zipPath)){
                    continue 2;
                }
            }

            if(is_dir($item)){
                $zip->addEmptyDir($zipPath);
            }elseif(is_file($item)){
                $zip->addFile($item,$zipPath);
            }
        }

        if(!$zip->close()){
            throw new Exception("Couldn't close zip file!");
        }

        return $zip;
    }

    public static function createPhar($sourceDir,$destinationFile,$excludeFilePattern = array(), $options = array()){
        list($sourceDir,$destinationFile,$items) = self::prepareArchive($sourceDir,$destinationFile);

        $alias = $options['alias'] ? $options['alias'] : 'archive.phar';

        $phar = new Phar($destinationFile,0,$alias);
        if($options['compress']){
            $phar = $phar->convertToExecutable(Phar::TAR, Phar::GZ);
        }

        foreach($items as $item){
            $zipPath = str_replace($sourceDir,'',$item);

            foreach((array)$excludeFilePattern as $excludePattern){
                if(preg_match($excludePattern,$zipPath)){
                    continue 2;
                }
            }

            if(is_dir($item)){
                $phar->addEmptyDir($zipPath);
            }elseif(is_file($item)){
                $phar->addFile($item,$zipPath);
            }
        }

        if($metaData = $options['metaData']){
            $phar->setMetadata($metaData);
        }
        $phar->stopBuffering();
        return $phar;
    }

    protected static function prepareArchive($sourceDir,$destinationFile){
        if(substr($sourceDir,-1,1) != DIRECTORY_SEPARATOR){
            $sourceDir .= DIRECTORY_SEPARATOR;
        }

        if(is_dir($sourceDir) && is_readable($sourceDir)){
            $items = rscandir($sourceDir);
        }else{
            throw new Exception("$sourceDir doesn't exits or is not readable!");
        }

        if(!$destinationFile || !is_string($destinationFile)){
            throw new Exception('No destinationFile provided!');
        }else{
            @unlink($destinationFile);
        }

        $destinationDir = dirname($destinationFile);
        if(!is_dir($destinationDir)){
            Pimcore_File::mkdir($destinationDir);
        }
        return array($sourceDir,$destinationFile,$items);
    }

}