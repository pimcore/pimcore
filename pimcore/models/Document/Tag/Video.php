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
    public static $swfObjectEmbedded = false;

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
        return array(
            "id" => $this->id,
            "type" => $this->type
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
        return $dependencies;
    }

    /**
     * @return bool
     */
    public function sanityCheck()
    {

        $sane = true;
        if ($this->type == "asset" && !empty($this->id)) {
            $el = Asset::getById($this->id);
            if (!$el instanceof Asset) {
                $sane = false;
                logger::notice("Detected insane relation, removing reference to non existent asset with id [" . $this->id . "]");
                $this->id = null;
                $this->type = null;
            }
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
            $data = unserialize($data);
        }

        $this->id = $data["id"];
        $this->type = $data["type"];
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data)
    {
        $this->id = $data["id"];

        if ($data["type"]) {
            $this->type = $data["type"];
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
        $path = Asset::getById($this->id);
        return $this->getFlowplayerCode((string)$path);
    }

    public function getUrlCode()
    {
        return $this->getFlowplayerCode($this->id);
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
        $parts = parse_url($this->id);
        parse_str($parts["query"], $vars);

        if (!$vars["v"] || strpos($parts["host"], "youtube.com") === false) {
            return $this->getEmptyCode();
            //return $this->getFlowplayerCode();
        }

        $code .= $this->getSwfObject();

        $youtubeId = $vars["v"];

        $code .= '<div id="pimcore_video_' . $this->getName() . '"><div id="' . $uid . '"></div></div>';
        $code .= '
            <script type="text/javascript">
				swfobject.embedSWF("http://www.youtube.com/v/' . $youtubeId . '?fs=1&rel=0", "' . $uid . '", "' . $this->getWidth() . '", "' . $this->getHeight() . '", "10.0.0", "", ' . Zend_Json::encode($this->getOptions()) . ', {quality: "high",wmode: "transparent",scale: "noscale",allowfullscreen: "true",allowscriptaccess: "always"});
			</script>
        ';

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

        $code .= $this->getSwfObject();

        $code .= '<div id="pimcore_video_' . $this->getName() . '"><div id="' . $uid . '"></div></div>';
        $code .= '
            <script type="text/javascript">
				swfobject.embedSWF("http://vimeo.com/moogaloop.swf?clip_id=' . $vimeoId . '&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1", "' . $uid . '", "' . $this->getWidth() . '", "' . $this->getHeight() . '", "10.0.0", "", ' . Zend_Json::encode($this->getOptions()) . ', {quality: "high",wmode: "transparent",scale: "noscale",allowfullscreen: "true",allowscriptaccess: "always"});
			</script>
        ';

        return $code;
    }

    public function getSwfObject()
    {
        $code = "";
        if (!Document_Tag_Video::$swfObjectEmbedded) {
            $code = '<script type="text/javascript" src="/pimcore/static/js/lib/swfobject/swfobject.js"></script>';
            Document_Tag_Video::$swfObjectEmbedded = true;
        }
        return $code;
    }

    public function getFlowplayerCode($url = null)
    {

        $options = $this->getOptions();
        $code = "";
        $scriptPath = "/pimcore/static/js/lib/flowplayer/flowplayer-3.2.0.min.js";
        $swfPath = "/pimcore/static/js/lib/flowplayer/flowplayer-3.2.1.swf";
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

        $config["clip"]["url"] = $url;
        if (!$url) {
            return $this->getEmptyCode();
            //$config["clip"]["url"] = "/pimcore/static/f4v/pimcore.f4v";
        }

        if (!Document_Tag_Video::$playerJsEmbedded) {
            $code .= '<script type="text/javascript" src="' . $scriptPath . '"></script>';
            $code .= '<script type="text/javascript" src="/pimcore/static/js/lib/array_merge.js"></script>';
            $code .= '<script type="text/javascript" src="/pimcore/static/js/lib/array_merge_recursive.js"></script>';
            Document_Tag_Video::$playerJsEmbedded = true;
        }

        $code .= '<div id="pimcore_video_' . $this->getName() . '"><div id="' . $uid . '"></div></div>';

        Zend_Json::encode($config);

        $code .= '
            <script type="text/javascript">
            	var player_config_' . $uid . ' = array_merge_recursive(' . $preConfig . ',' . Zend_Json::encode($config) . ');
                
                flowplayer("' . $uid . '", {
            		src: "' . $swfPath . '",
            		width: "' . $this->getWidth() . '",
            		height: ' . $this->getHeight() . ',
                    wmode: "transparent"
            	},player_config_' . $uid . ');
            </script>
        ';

        return $code;
    }

    public function getEmptyCode()
    {
        $uid = "video_" . uniqid();
        return '<div id="pimcore_video_' . $this->getName() . '"><div class="pimcore_tag_video_empty" id="' . $uid . '" style="width: ' . $this->getWidth() . 'px; height: ' . $this->getHeight() . 'px;"></div></div>';
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

}
