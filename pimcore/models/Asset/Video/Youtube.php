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
 * @package    Asset
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */ 

class Asset_Video_Youtube {
    
    public function maintenanceupload () {
        
        
        $list = new Asset_List();
        $list->setCondition("type = 'video' AND customSettings NOT LIKE '%youtube%'");
        
        $assets = $list->load();
        
        foreach ($assets as $asset) {
            self::upload($asset);
        }
    }
    
    
    public static function upload ($asset) {
        
        try {
        
            $credentials = Asset_Video_Youtube::getYoutubeCredentials();
            
            if(!$credentials) {
                return;
            }
            
            $httpClient = Zend_Gdata_ClientLogin::getHttpClient(
                $username = $credentials["username"],
                $password = $credentials["password"],
                $service = 'youtube',
                $client = Pimcore_Tool::getHttpClient("Zend_Gdata_HttpClient"),
                $source = 'Pimcore',
                $loginToken = null,
                $loginCaptcha = null,
                'https://www.google.com/youtube/accounts/ClientLogin'
            );
    
            $httpClient->setConfig(array(
                "timeout" => 3600
            ));
    
            $apikey = $credentials["apiKey"];
            $httpClient->setHeaders('X-GData-Key', "key=${apikey}");
    
    
            $yt = new Zend_Gdata_YouTube($httpClient);
            $myVideoEntry = new Zend_Gdata_YouTube_VideoEntry();
    
            $filesource = $yt->newMediaFileSource($asset->getFileSystemPath());
            $filesource->setContentType($asset->getMimetype());
            $filesource->setSlug($asset->getFilename());
    
            $myVideoEntry->setMediaSource($filesource);
    
            $myVideoEntry->setVideoTitle($asset->getFullPath());
            $myVideoEntry->setVideoDescription($asset->getFullPath());
    
            $myVideoEntry->setVideoCategory('Comedy');
    
            // Set keywords, note that this must be a comma separated string
            // and that each keyword cannot contain whitespace
            $myVideoEntry->SetVideoTags('---, ---');
    
            // Optionally set some developer tags
            $myVideoEntry->setVideoDeveloperTags(array(
                'mydevelopertag',
                'anotherdevelopertag'
            ));
    
            // Upload URI for the currently authenticated user
            $uploadUrl = 'http://uploads.gdata.youtube.com/feeds/users/default/uploads';
    
            try {
                $newEntry = $yt->insertEntry($myVideoEntry, $uploadUrl, 'Zend_Gdata_YouTube_VideoEntry');
    
                $asset->setCustomSetting("youtube", array(
                    "id" => strval($newEntry->getVideoId())
                ));
                $asset->save();
                
                return true;
            } catch (Exception $e) {
                $asset->setCustomSetting("youtube", array(
                    "failed" => true
                ));
                $asset->save();
            }
        }
        catch (Exception $e) {
            Logger::error($e);
        }
        
        return false;
    }
    
    
    public static function getYoutubeCredentials () {
        $googleCredentials = Pimcore_Config::getSystemConfig()->services->google;
        $youtubeConf = Pimcore_Config::getSystemConfig()->services->youtube;
        
        if (!self::validateYoutubeConf($googleCredentials, $youtubeConf)) {
            return;
        }
        
        return array(
            "username" => $googleCredentials->username,
            "password" => $googleCredentials->password,
            "apiKey" => $youtubeConf->apikey
        );
    }
    
    /**
     *
     * @param unknown_type $conf
     * @return boolean $valid
     */
    public static function validateYoutubeConf($googleCredentials, $youtubeConf) {
        $valid = true;
        if (empty($googleCredentials) || empty($youtubeConf)) {
            $valid = false;
        } else {
            if ($youtubeConf->apikey == null or $youtubeConf->apikey == "") $valid = false;
        }
        return $valid;
    }
}
