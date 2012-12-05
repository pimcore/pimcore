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
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Tag_Video extends Document_Tag
{

    public static $playerJsEmbedded = false;

    /**
     * contains depending on the type of the video the unique identifier eg. "http://www.youtube.com", "789", ...
     *
     * @var mixed
     */
    public $id;

    /**
     * one of asset, youtube, vimeo
     * @var string
     */
    public $type = "asset";

    /**
     * asset ID of poster image
     * @var int
     */
    public $poster;

    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType()
    {
        return "video";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData()
    {
        $path = $this->id;
        if($this->type == "asset" && ($video = Asset::getById($this->id))) {
            $path = $video->getFullPath();
        }

        $poster = Asset::getById($this->poster);

        return array(
            "id" => $this->id,
            "type" => $this->type,
            "path" => $path,
            "poster" => $poster ? $poster->getFullPath() : ""
        );
    }

    /**
     *
     */
    public function getDataForResource() {
        return array(
            "id" => $this->id,
            "type" => $this->type,
            "poster" => $this->poster
        );
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return string
     */
    public function frontend()
    {

        if (!$this->id || !$this->type) {
            return $this->getEmptyCode();
            //return $this->getFlowplayerCode();
        }
        else if ($this->type == "asset") {
            return $this->getAssetCode();
        }
        else if ($this->type == "youtube") {
            return $this->getYoutubeCode();
        }
        else if ($this->type == "vimeo") {
            return $this->getVimeoCode();
        }
        else if ($this->type == "url") {
            return $this->getUrlCode();
        }

        return $this->getEmptyCode();
        //return $this->getFlowplayerCode();
    }


    /**
     * @return array
     */
    public function resolveDependencies()
    {

        $dependencies = array();

        if ($this->type == "asset") {
            $asset = Asset::getById($this->id);
            if ($asset instanceof Asset) {
                $key = "asset_" . $asset->getId();
                $dependencies[$key] = array(
                    "id" => $asset->getId(),
                    "type" => "asset"
                );
            }
        }

        if($poster = Asset::getById($this->poster)) {
            $key = "asset_" . $poster->getId();
            $dependencies[$key] = array(
                "id" => $poster->getId(),
                "type" => "asset"
            );
        }

        return $dependencies;
    }

    /**
     * @return bool
     */
    public function checkValidity()
    {
        $sane = true;
        if ($this->type == "asset" && !empty($this->id)) {
            $el = Asset::getById($this->id);
            if (!$el instanceof Asset) {
                $sane = false;
                Logger::notice("Detected insane relation, removing reference to non existent asset with id [" . $this->id . "]");
                $this->id = null;
                $this->type = null;
            }
        }

        if(!($poster = Asset::getById($this->poster))) {
            $sane = false;
            Logger::notice("Detected insane relation, removing reference to non existent asset with id [" . $this->id . "]");
            $this->poster = null;
        }

        return $sane;
    }


    /**
     * @see Document_Tag_Interface::admin
     * @return string
     */
    public function admin()
    {

        $html = parent::admin();

        // get frontendcode for preview
        $html .= $this->frontend();

        return $html;
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data)
    {
        if (!empty($data)) {
            $data = Pimcore_Tool_Serialize::unserialize($data);
        }

        $this->id = $data["id"];
        $this->type = $data["type"];
        $this->poster = $data["poster"];
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data)
    {
        if ($data["type"]) {
            $this->type = $data["type"];
        }

        // this is to be backward compatible to <= v 1.4.7
        if($data["id"]){
            $data["path"] = $data["id"];
        }

        $video = Asset::getByPath($data["path"]);
        if($video instanceof Asset_Video) {
            $this->id = $video->getId();
        } else {
            $this->id = $data["path"];
        }

        $this->poster = null;
        $poster = Asset::getByPath($data["poster"]);
        if($poster instanceof Asset_Image) {
            $this->poster = $poster->getId();
        }
    }


    public function getWidth()
    {
        $options = $this->getOptions();
        if ($options["width"]) {
            return $options["width"];
        }
        return "100%";
    }

    public function getHeight()
    {
        $options = $this->getOptions();
        if ($options["height"]) {
            return $options["height"];
        }
        return 300;
    }


    public function getAssetCode()
    {
        $asset = Asset::getById($this->id);

        // compatibility mode when FFMPEG is not present
        if(!Pimcore_Video::isAvailable()) {
            // try to load the assigned asset into the flowplayer
            return $this->getFlowplayerCode(array("mp4" => (string) $asset));
        }

        $options = $this->getOptions();
        if ($asset instanceof Asset_Video && $options["thumbnail"]) {
            $thumbnail = $asset->getThumbnail($options["thumbnail"]);
            if ($thumbnail) {

                if(!array_key_exists("imagethumbnail", $options) || empty($options["imagethumbnail"])) {
                    // try to get the dimensions out ouf the video thumbnail
                    $imageThumbnailConf = array();
                    $thumbnailConf = $asset->getThumbnailConfig($options["thumbnail"]);
                    $transformations = $thumbnailConf->getItems();
                    if(is_array($transformations) && count($transformations) > 0) {
                        foreach ($transformations as $transformation) {
                            if(!empty($transformation)) {
                                if(is_array($transformation["arguments"])) {
                                    foreach ($transformation["arguments"] as $key => $value) {
                                        if($key == "width" || $key == "height") {
                                            $imageThumbnailConf[$key] = $value;
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $imageThumbnailConf = $options["imagethumbnail"];
                }

                if(empty($imageThumbnailConf)) {
                    $imageThumbnailConf["width"] = 800;
                }

                if($this->poster && ($poster = Asset::getById($this->poster))) {
                    $image = $poster->getThumbnail($imageThumbnailConf);
                } else {
                    $image = $asset->getImageThumbnail($imageThumbnailConf);
                }

                if ($thumbnail["status"] == "finished") {
                    if($options["html5"]) {
                        return $this->getHtml5Code($thumbnail["formats"], $image);
                    } else {
                        return $this->getFlowplayerCode($thumbnail["formats"], $image);
                    }
                } else if ($thumbnail["status"] == "inprogress") {
                    // disable the output-cache if enabled
                    $front = Zend_Controller_Front::getInstance();
                    $front->unregisterPlugin("Pimcore_Controller_Plugin_Cache");

                    $progress = Asset_Video_Thumbnail_Processor::getProgress($thumbnail["processId"]);
                    return $this->getProgressCode($progress, $image);
                } else {
                    return $this->getErrorCode("The video conversion failed, please see the debug.log for more details.");
                }
            } else {
                return $this->getErrorCode("The given thumbnail doesn't exist");
            }
        } else {

            // try to load the assigned asset into the flowplayer (backward compatibility only for f4v, flv, and mp4 files)
            if($asset instanceof Asset && preg_match("/\.(f4v|flv|mp4)/", $asset->getFullPath())) {
                // try to generate thumbnail with ffmpeg if installed
                if(Pimcore_Video::isAvailable()) {
                    $image = $asset->getImageThumbnail(array("width" => array_key_exists("width", $options) ? $options["width"] : 800));
                }
                return $this->getFlowplayerCode(array("mp4" => (string) $asset), $image);
            }

            return $this->getErrorCode("Asset is not a video, or missing thumbnail configuration");
        }
    }

    protected function getScheme () {
        if($this->view instanceof Zend_View && $this->view->getRequest()) {
            return $this->view->getRequest()->getScheme();
        } else {
            return "https";
        }
    }

    public function getUrlCode()
    {
        return $this->getFlowplayerCode(array("mp4" => (string) $this->id));
    }

    public function getErrorCode($message = "") {

        $width = $this->getWidth();
        if(strpos($this->getWidth(), "%") === false) {
            $width = ($this->getWidth()-1) . "px";
        }

        // only display error message in debug mode
        if(!Pimcore::inDebugMode()) {
            $message = "";
        }

        $code = '
        <div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video">
            <div class="pimcore_tag_video_error" style="text-align:center; width: ' . $width . '; height: ' . ($this->getHeight()-1) . 'px; border:1px solid #000; background: url(/pimcore/static/img/filetype-not-supported.png) no-repeat center center #fff;">
                ' . $message . '
            </div>
        </div>';

        return $code;
    }

    public function getYoutubeCode()
    {

        if (!$this->id) {
            return $this->getEmptyCode();
            //return $this->getFlowplayerCode();
        }

        $options = $this->getOptions();
        $code = "";
        $uid = "video_" . uniqid();

        // get youtube id
        $youtubeId = null;
        $parts = parse_url($this->id);
        parse_str($parts["query"], $vars);

        if($vars["v"]) {
            $youtubeId = $vars["v"];
        }

        //get youtube id if form urls like  http://www.youtube.com/embed/youtubeId
        if(!$youtubeId && strpos($this->id,'embed') !== false){
            $explodedPath = explode('/',$parts['path']);
            $youtubeId = $explodedPath[array_search('embed',$explodedPath)+1];
        }


        if(!$youtubeId && $parts["host"] == "youtu.be") {
            $youtubeId = trim($parts["path"]," /");
        }

        if (!$youtubeId) {
            return $this->getEmptyCode();
            //return $this->getFlowplayerCode();
        }


        $width = "100%";
        if(array_key_exists("width", $options)) {
            $width = $options["width"];
        }


        $width = "100%";
        if(array_key_exists("width", $options)) {
            $width = $options["width"];
        }

        $height = "300";
        if(array_key_exists("height", $options)) {
            $height = $options["height"];
        }
        /*
       if($options["config"]["clip"]["autoPlay"]){
           $autoPlayString = "&autoplay=1";
       } */

        $valid_youtube_prams=array( "autohide",
            "autoplay",
            "cc_load_policy",
            "color",
            "controls",
            "disablekb",
            "enablejsapi",
            "end",
            "fs",
            "iv_load_policy",
            "list",
            "listType",
            "loop",
            "modestbranding",
            "origin",
            "playerapiid",
            "playlist",
            "rel",
            "showinfo",
            "start",
            "theme");
        $additional_params="";

        $clipConfig = array();
        if(is_array($options["config"]["clip"])) {
            $clipConfig = $options["config"]["clip"];
        }

        // this is to be backward compatible to <= v 1.4.7
        $configurations = $clipConfig;
        if(is_array($options["youtube"])){
            $configurations = array_merge($clipConfig, $options["youtube"]);
        }

        if(!empty($configurations)){
            foreach($configurations as $key=>$value){
                if(in_array($key, $valid_youtube_prams)){
                    if(is_bool($value)){
                        if($value){
                            $additional_params.="&".$key."=1";
                        }else{
                            $additional_params.="&".$key."=0";
                        }
                    }else{
                        $additional_params.="&".$key."=".$value;
                    }
                }
            }
        }

        $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video">
            <iframe width="' . $width . '" height="' . $height . '" src="' . $this->getScheme() . '://www.youtube.com/embed/' . $youtubeId . '?wmode=transparent' . $additional_params .'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
        </div>';

        return $code;
    }

    public function getVimeoCode()
    {
        if (!$this->id) {
            return $this->getEmptyCode();
            //return $this->getFlowplayerCode();
        }

        $options = $this->getOptions();
        $code = "";
        $uid = "video_" . uniqid();

        // get youtube id
        $parts = parse_url($this->id);
        $pathParts = explode("/", $parts["path"]);
        $vimeoId = intval($pathParts[1]);

        if (!$vimeoId || strpos($parts["host"], "vimeo.com") === false) {
            return $this->getEmptyCode();
            //return $this->getFlowplayerCode();
        }

        $width = "100%";
        if(array_key_exists("width", $options)) {
            $width = $options["width"];
        }

        $height = "300";
        if(array_key_exists("height", $options)) {
            $height = $options["height"];
        }

        $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video">
            <iframe src="' . $this->getScheme() . '://player.vimeo.com/video/' . $vimeoId . '?title=0&amp;byline=0&amp;portrait=0" width="' . $width . '" height="' . $height . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
        </div>';

        return $code;
    }

    public function getFlowplayerCode($urls = array(), $thumbnail = null)
    {

        $options = $this->getOptions();
        $code = "";
        $scriptPath = "/pimcore/static/js/lib/flowplayer/flowplayer.min.js";
        $swfPath = "/pimcore/static/js/lib/flowplayer/flowplayer.swf";
        $uid = "video_" . uniqid();
        $config = array();

        // configurations
        if ($options["swfPath"]) {
            $swfPath = $options["swfPath"];
        }
        if ($options["scriptPath"]) {
            $scriptPath = $options["scriptPath"];
        }

        $preConfig = Zend_Json::encode(array("dummy" => true));
        if ($options["config"]) {
            if (is_string($options["config"])) {
                // configuration is the name of the javascript variable which contains the configuration
                $preConfig = $options["config"];
            }
            else if (is_array($options["config"])) {
                // configuration is directly in php, so wh have to convert it to json
                $preConfig = Zend_Json::encode($options["config"]);
            }
        }

        $config["clip"]["url"] = $urls["mp4"];
        if (empty($urls)) {
            return $this->getEmptyCode();
            //$config["clip"]["url"] = "/pimcore/static/f4v/pimcore.f4v";
        }

        if (!Document_Tag_Video::$playerJsEmbedded) {
            $code .= '<script type="text/javascript" src="' . $scriptPath . '"></script>';
            $code .= '<script type="text/javascript" src="/pimcore/static/js/lib/array_merge.js"></script>';
            $code .= '<script type="text/javascript" src="/pimcore/static/js/lib/array_merge_recursive.js"></script>';
            Document_Tag_Video::$playerJsEmbedded = true;

            $code .= '

                <style type="text/css">
                    a.pimcore_video_flowplayer {
                        display:block;
                        text-align:center;
                    }
                </style>
            ';
        }

        if (Pimcore_Video::isAvailable()) {
            $code .= '
                <style type="text/css">
                    #' . $uid . ' .play {
                        margin-top:' . (($this->getHeight()-83)/2) . 'px;
                        border:0px;
                        display:inline-block;
                        width:83px;
                        height:83px;
                        background:url(/pimcore/static/js/lib/flowplayer/play_large.png);
                    }
                </style>
            ';
        }

        $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video">
            <a id="' . $uid . '"
            	href="'.$urls["mp4"].'"
            	class="pimcore_video_flowplayer"
            	style="background:url(' . $thumbnail . ') no-repeat center center; width:' . $this->getWidth() . 'px; height:' . $this->getHeight() . 'px;">
            	' . (Pimcore_Video::isAvailable() ? '<span class="play">' : "") .'</span>
            </a>
        </div>';

        Zend_Json::encode($config);

        $code .= '
            <script type="text/javascript">
            	var player_config_' . $uid . ' = array_merge_recursive(' . $preConfig . ',' . Zend_Json::encode($config) . ');
                
                flowplayer("' . $uid . '", {
            		src: "' . $swfPath . '",
            		width: "' . $this->getWidth() . '",
            		height: "' . $this->getHeight() . '",
            	},player_config_' . $uid . ');
            </script>
        ';

        return $code;
    }

    public function getHtml5Code($urls = array(), $thumbnail = null)
    {
        $code = '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video">';
        $code .= '<video class="pimcore_video" width="' . $this->getWidth() . '" height="' . $this->getHeight() . '" poster="' . $thumbnail . '" controls="controls" preload="none">';
            foreach ($urls as $type => $url) {
                $code .= '<source type="video/' . $type . '" src="' . $url . '" />';
            }
        $code .= '</video>';
        $code .= '</div>';

        return $code;
    }

    public function getProgressCode($progress, $thumbnail = null)
    {
        $uid = "video_" . uniqid();
        $code = '
        <div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video">
            <style type="text/css">
                #' . $uid . ' {
                    position:relative;
                    background:#555 url('. $thumbnail . ') center center no-repeat;
                }
                #' . $uid . ' .pimcore_tag_video_progress_status {
                    font-size:18px;
                    color:#555;
                    font-family:Arial,Verdana,sans-serif;
                    line-height:66px;
                    background:#fff url(/pimcore/static/img/video-loading.gif) center center no-repeat;
                    width:66px;
                    height:66px;
                    padding:20px;
                    border:1px solid #555;
                    text-align:center;
                    box-shadow: 2px 2px 5px #333;
                    border-radius:20px;
                    margin: 0 20px 0 20px;
                    top: ' . (($this->getHeight()-106)/2) . 'px;
                    left: ' . (($this->getWidth()-106)/2) . 'px;
                    position:absolute;
                    opacity: 0.8;
                }
            </style>
            <div class="pimcore_tag_video_progress" id="' . $uid . '" style="width: ' . $this->getWidth() . 'px; height: ' . $this->getHeight() . 'px;">
                <div class="pimcore_tag_video_progress_status">' . number_format($progress,0) . '%</div>
            </div>
        </div>';

        $options = $this->getOptions();

        if(!$this->editmode && !$options['disableProgressReload']) {
            $code .= '
                <script type="text/javascript">
                    window.setTimeout(function() {
                        location.reload();
                    },6000);
                </script>
            ';
        }

        return $code;
    }

    public function getEmptyCode()
    {
        $uid = "video_" . uniqid();
        return '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video"><div class="pimcore_tag_video_empty" id="' . $uid . '" style="width: ' . $this->getWidth() . 'px; height: ' . $this->getHeight() . 'px;"></div></div>';
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        if ($this->id) {
            return false;
        }
        return true;
    }


    /**
     * Receives a Webservice_Data_Document_Element from webservice import and fill the current tag's data
     *
     * @abstract
     * @param  Webservice_Data_Document_Element $data
     * @return void
     */
    public function getFromWebserviceImport($wsElement)
    {
        $data = $wsElement->value;
        if($data->id){
            if ($data->type == "asset") {

                $this->id = $data->id;
                $asset = Asset::getById($data->id);
                if(!$asset){
                    throw new Exception("Referencing unknown asset with id [ ".$data->id." ] in webservice import field [ ".$data->name." ]");
                }
                $this->type = $data->type;

            } else if (in_array($data->type,array("vimeo","youtube","url"))) {
                  $this->id = $data->id;
                  $this->type = $data->type;
            } else {
                throw new Exception("cannot get values from web service import - type must be asset,youtube,url or vimeo ");
            }
        }


    }


    /**
     * @return string
     */
    public function getVideoType() {
        return $this->type;
    }

    /**
     * @return Asset
     */
    public function getVideoAsset() {
        if($this->getVideoType() == "asset") {
            return Asset::getById($this->id);
        }
    }

    /**
     * @return Asset
     */
    public function getPosterAsset() {
        return Asset::getById($this->poster);
    }

    /**
     * @param $config
     * @return string
     */
    public function getImageThumbnail($config) {
        if($this->poster && ($poster = Asset::getById($this->poster))) {
            return $poster->getThumbnail($config);
        }

        if($this->getVideoAsset()) {
            return $this->getVideoAsset()->getImageThumbnail($config);
        }
        return "";
    }

    /**
     * @param $config
     * @return array
     */
    public function getThumbnail($config) {
        if($this->getVideoAsset()) {
            return $this->getVideoAsset()->getThumbnail($config);
        }
        return array();
    }
}
