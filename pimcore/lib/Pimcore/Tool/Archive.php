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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Tool_Archive {

    /**
     * @param $sourceDir Source directory
     * @param $destinationFile destination zip file
     * @param array $excludeFilePattern exclude files
     * @param int $mode Ziparchive open mode
     * @return bool
     * @throws Exception
     */
    public static function createZip($sourceDir,$destinationFile,$excludeFilePattern = array(), $mode = ZIPARCHIVE::OVERWRITE){

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
        }

        $destinationDir = dirname($destinationFile);
        if(!is_dir($destinationDir)){
            mkdir($destinationDir,0755,true);
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

}