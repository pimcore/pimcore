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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;
use Pimcore\Tool;
use Pimcore\Model\Asset;

class Video extends Model\Document\Tag
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
     * @var string
     */
    public $title = "";

    /**
     * @var string
     */
    public $description = "";

    /**
     * @param $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if(!$this->title && $this->getVideoAsset()) {
            // default title for microformats
            return $this->getVideoAsset()->getFilename();
        }
        return $this->title;
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        if(!$this->description) {
            // default description for microformats
            return $this->getTitle();
        }
        return $this->description;
    }

    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType()
    {
        return "video";
    }

    /**
     * @see Document\Tag\TagInterface::getData
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
            "title" => $this->title,
            "description" => $this->description,
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
            "title" => $this->title,
            "description" => $this->description,
            "poster" => $this->poster
        );
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return string
     */
    public function frontend()
    {

        $inAdmin = false;
        $args = func_get_args();
        if(array_key_exists(0, $args)) {
            $inAdmin = $args[0];
        }

        if (!$this->id || !$this->type) {
            return $this->getEmptyCode();
        }
        else if ($this->type == "asset") {
            return $this->getAssetCode($inAdmin);
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
                \Logger::notice("Detected insane relation, removing reference to non existent asset with id [" . $this->id . "]");
                $this->id = null;
                $this->type = null;
            }
        }

        if(!($poster = Asset::getById($this->poster))) {
            $sane = false;
            \Logger::notice("Detected insane relation, removing reference to non existent asset with id [" . $this->id . "]");
            $this->poster = null;
        }

        return $sane;
    }


    /**
     * @see Document\Tag\TagInterface::admin
     * @return string
     */
    public function admin()
    {

        $html = parent::admin();

        // get frontendcode for preview
        // put the video code inside the generic code
        $html = str_replace("</div>", $this->frontend(true) . "</div>", $html);

        return $html;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data)
    {
        if (!empty($data)) {
            $data = \Pimcore\Tool\Serialize::unserialize($data);
        }

        $this->id = $data["id"];
        $this->type = $data["type"];
        $this->poster = $data["poster"];
        $this->title = $data["title"];
        $this->description = $data["description"];
        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data)
    {
        if ($data["type"]) {
            $this->type = $data["type"];
        }

        if ($data["title"]) {
            $this->title = $data["title"];
        }

        if ($data["description"]) {
            $this->description = $data["description"];
        }

        // this is to be backward compatible to <= v 1.4.7
        if($data["id"]){
            $data["path"] = $data["id"];
        }

        $video = Asset::getByPath($data["path"]);
        if($video instanceof Asset\Video) {
            $this->id = $video->getId();
        } else {
            $this->id = $data["path"];
        }

        $this->poster = null;
        $poster = Asset::getByPath($data["poster"]);
        if($poster instanceof Asset\Image) {
            $this->poster = $poster->getId();
        }
        return $this;
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


    public function getAssetCode($inAdmin = false)
    {
        $asset = Asset::getById($this->id);
        $options = $this->getOptions();

        // compatibility mode when FFMPEG is not present or no thumbnail config is given
        if(!\Pimcore\Video::isAvailable() || !$options["thumbnail"]) {
            if($asset instanceof Asset && preg_match("/\.(f4v|flv|mp4)/", $asset->getFullPath())) {
                return $this->getHtml5Code(array("mp4" => (string) $asset));
            }

            return $this->getErrorCode("Asset is not a video, or missing thumbnail configuration");
        }

        if ($asset instanceof Asset\Video && $options["thumbnail"]) {
            $thumbnail = $asset->getThumbnail($options["thumbnail"]);
            if ($thumbnail) {

                if(!array_key_exists("imagethumbnail", $options) || empty($options["imagethumbnail"])) {
                    // try to get the dimensions out ouf the video thumbnail
                    $imageThumbnailConf = $asset->getThumbnailConfig($options["thumbnail"])->getEstimatedDimensions();
                    $imageThumbnailConf["format"] = "JPEG";
                } else {
                    $imageThumbnailConf = $options["imagethumbnail"];
                }

                if(empty($imageThumbnailConf)) {
                    $imageThumbnailConf["width"] = 800;
                    $imageThumbnailConf["format"] = "JPEG";
                }

                if($this->poster && ($poster = Asset::getById($this->poster))) {
                    $image = $poster->getThumbnail($imageThumbnailConf);
                } else {
                    if($asset->getCustomSetting("image_thumbnail_asset")) {
                        $image = $asset->getImageThumbnail($imageThumbnailConf);
                    } else {
                        if ($thumbnail["status"] == "finished" && (array_key_exists("animatedGifPreview", $options) && $options["animatedGifPreview"] !== false)) {
                            $image = $asset->getPreviewAnimatedGif(null, null, $imageThumbnailConf);
                        } else {
                            $image = $asset->getImageThumbnail($imageThumbnailConf);
                        }
                    }
                }

                if($inAdmin && isset($options["editmodeImagePreview"]) && $options["editmodeImagePreview"]) {
                    $code = '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video">';
                    $code .= '<img width="' . $this->getWidth() . '" src="' . $image . '" />';
                    $code .= '</div';
                    return $code;
                }

                if ($thumbnail["status"] == "finished") {
                    return $this->getHtml5Code($thumbnail["formats"], $image);
                } else if ($thumbnail["status"] == "inprogress") {
                    // disable the output-cache if enabled
                    $front = \Zend_Controller_Front::getInstance();
                    $front->unregisterPlugin("Pimcore\\Controller\\Plugin\\Cache");

                    $progress = Asset\Video\Thumbnail\Processor::getProgress($thumbnail["processId"]);
                    return $this->getProgressCode($progress, $image);
                } else {
                    return $this->getErrorCode("The video conversion failed, please see the debug.log for more details.");
                }
            } else {
                return $this->getErrorCode("The given thumbnail doesn't exist: '" . $options["thumbnail"] . "'");
            }
        }
    }

    public function getUrlCode()
    {
        return $this->getHtml5Code(array("mp4" => (string) $this->id));
    }

    public function getErrorCode($message = "") {

        $width = $this->getWidth();
        if(strpos($this->getWidth(), "%") === false) {
            $width = ($this->getWidth()-1) . "px";
        }

        // only display error message in debug mode
        if(!\Pimcore::inDebugMode()) {
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
        }

        $options = $this->getOptions();
        $code = "";
        $uid = "video_" . uniqid();

        // get youtube id
        $youtubeId = $this->id;
        if(strpos($youtubeId, "//") !== false) {
            $parts = parse_url($this->id);
            parse_str($parts["query"], $vars);

            if($vars["v"]) {
                $youtubeId = $vars["v"];
            }

            //get youtube id if form urls like  http://www.youtube.com/embed/youtubeId
            if(strpos($this->id,'embed') !== false){
                $explodedPath = explode('/',$parts['path']);
                $youtubeId = $explodedPath[array_search('embed',$explodedPath)+1];
            }

            if($parts["host"] == "youtu.be") {
                $youtubeId = trim($parts["path"]," /");
            }
        }

        if (!$youtubeId) {
            return $this->getEmptyCode();
        }

        $width = "100%";
        if(array_key_exists("width", $options)) {
            $width = $options["width"];
        }

        $height = "300";
        if(array_key_exists("height", $options)) {
            $height = $options["height"];
        }

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
            <iframe width="' . $width . '" height="' . $height . '" src="//www.youtube.com/embed/' . $youtubeId . '?wmode=transparent' . $additional_params .'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
        </div>';

        return $code;
    }

    public function getVimeoCode()
    {
        if (!$this->id) {
            return $this->getEmptyCode();
        }

        $options = $this->getOptions();
        $code = "";
        $uid = "video_" . uniqid();

        // get vimeo id
        if(preg_match("@vimeo.*/([\d]+)@i", $this->id, $matches)) {
            $vimeoId = intval($matches[1]);

            $width = "100%";
            if(array_key_exists("width", $options)) {
                $width = $options["width"];
            }

            $height = "300";
            if(array_key_exists("height", $options)) {
                $height = $options["height"];
            }

            $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video">
                <iframe src="//player.vimeo.com/video/' . $vimeoId . '?title=0&amp;byline=0&amp;portrait=0" width="' . $width . '" height="' . $height . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
            </div>';

            return $code;
        }

        // default => return the empty code
        return $this->getEmptyCode();
    }

    public function getHtml5Code($urls = array(), $thumbnail = null)
    {
        $code = "";
        $video = $this->getVideoAsset();
        if($video) {
            $duration = ceil($video->getDuration());

            $durationParts = array("T");

            // hours
            if($duration/3600 >= 1) {
                $hours = floor($duration/3600);
                $durationParts[] = $hours . "H";
                $duration = $duration - $hours * 3600;
            }

            // minutes
            if($duration/60 >= 1) {
                $minutes = floor($duration/60);
                $durationParts[] = $minutes . "M";
                $duration = $duration - $minutes * 60;
            }

            $durationParts[] = $duration . "S";
            $durationString = implode("",$durationParts);

            $code .= '<div id="pimcore_video_' . $this->getName() . '" class="pimcore_tag_video" itemprop="video" itemscope itemtype="http://schema.org/VideoObject">' . "\n";
            $code .= '<meta itemprop="name" content="' . $this->getTitle() . '" />' . "\n";
            $code .= '<meta itemprop="description" content="' . $this->getDescription() . '" />' . "\n";
            $code .= '<meta itemprop="duration" content="' . $durationString . '" />' . "\n";
            $code .= '<meta itemprop="contentURL" content="' . Tool::getHostUrl() . $urls["mp4"] .  '" />' . "\n";
            if($thumbnail) {
                $code .= '<meta itemprop="thumbnailURL" content="' . Tool::getHostUrl() . $thumbnail . '" />' . "\n";
            }


            // default attributes
            $attributesString = "";
            $attributes = array(
                "width" => $this->getWidth(),
                "height" => $this->getHeight(),
                "poster" => $thumbnail,
                "controls" => "controls",
                "class" => "pimcore_video"
            );

            if(array_key_exists("attributes", $this->getOptions())) {
                $attributes = array_merge($attributes, $this->getOptions()["attributes"]);
            }

            foreach($attributes as $key => $value) {
                $attributesString .= " " . $key;
                if(!empty($value)) {
                    $quoteChar = '"';
                    if(strpos($value, '"')) {
                        $quoteChar = "'";
                    }
                    $attributesString .= '=' . $quoteChar . $value . $quoteChar;
                }
            }

            $code .= '<video' . $attributesString . '>' . "\n";

            $urls = array_reverse($urls); // use webm as the preferred format

            foreach ($urls as $type => $url) {
                $code .= '<source type="video/' . $type . '" src="' . $url . '" />' . "\n";
            }

            $code .= '</video>' . "\n";
            $code .= '</div>' . "\n";
        }

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
                    box-sizing:content-box;
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
                    left: 50%;
                    margin-left:-66px;
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
     * @param Model\Document\Webservice\Data\Document\Element $wsElement
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $idMapper = null)
    {
        $data = $wsElement->value;
        if($data->id){
            if ($data->type == "asset") {

                $this->id = $data->id;
                $asset = Asset::getById($data->id);
                if(!$asset){
                    throw new \Exception("Referencing unknown asset with id [ ".$data->id." ] in webservice import field [ ".$data->name." ]");
                }
                $this->type = $data->type;

            } else if (in_array($data->type,array("vimeo","youtube","url"))) {
                $this->id = $data->id;
                $this->type = $data->type;
            } else {
                throw new \Exception("cannot get values from web service import - type must be asset,youtube,url or vimeo ");
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

    /**
     * @param mixed $id
     * @return Video
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
        return (int) $this->id;
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param array $idMapping
     * @return void
     */
    public function rewriteIds($idMapping) {
        if($this->type == "asset" && array_key_exists("asset", $idMapping) and array_key_exists($this->getId(), $idMapping["asset"])) {
            $this->setId($idMapping["asset"][$this->getId()]);
        }
    }
}
