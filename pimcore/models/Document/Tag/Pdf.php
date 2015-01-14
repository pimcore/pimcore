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
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Model\Document;

class Pdf extends Model\Document\Tag
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var array
     */
    public $hotspots = array();

    /**
     * @var array
     */
    public $texts = array();

    /**
     * @var array
     */
    public $chapters = array();

    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType()
    {
        return "pdf";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData()
    {
        return array(
            "id" => $this->id,
            "hotspots" => $this->hotspots,
            "texts" => $this->texts,
            "chapters" => $this->chapters
        );
    }

    public function getDataForResource() {

        $rewritePath = function ($data) {

            if(!is_array($data)) {
                return array();
            }

            foreach ($data as &$page) {
                foreach ($page as &$element) {
                    if(array_key_exists("data",$element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach($element["data"] as &$metaData) {
                            if($metaData["value"] instanceof Element\ElementInterface) {
                                $metaData["value"] = $metaData["value"]->getId();
                            }
                        }
                    }
                }
            }

            return $data;
        };

        $hotspots = $rewritePath($this->hotspots);

        return array(
            "id" => $this->id,
            "hotspots" => $hotspots,
            "texts" => $this->getTexts(),
            "chapters" => $this->getChapters()
        );
    }

    public function getDataEditmode() {


        $rewritePath = function ($data) {

            if(!is_array($data)) {
                return array();
            }

            foreach ($data as &$page) {
                foreach ($page as &$element) {
                    if (array_key_exists("data", $element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach ($element["data"] as &$metaData) {
                            if ($metaData["value"] instanceof Element\ElementInterface) {
                                $metaData["value"] = $metaData["value"]->getFullPath();
                            }
                        }
                    }
                }
            }
            return $data;
        };

        $hotspots = $rewritePath($this->hotspots);

        $pages = 0;
        if($asset = Asset::getById($this->id)) {
            $pages = $asset->getPageCount();
        }

        $texts = $this->texts;
        // force an object when converting to JSON
        $texts["__dummy"] = "__dummy";

        return array(
            "id" => $this->id,
            "pageCount" => $pages,
            "hotspots" => empty($hotspots) ? null : $hotspots,
            "texts" => $texts,
            "chapters" => $this->chapters
        );
    }

    public function getCacheTags($ownerDocument, $tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        $asset = Asset::getById($this->id);
        if ($asset instanceof Asset) {
            if (!array_key_exists($asset->getCacheTag(), $tags)) {
                $tags = $asset->getCacheTags($tags);
            }

            $getMetaDataCacheTags = function ($data, $tags) {

                if(!is_array($data)) {
                    return $tags;
                }

                foreach ($data as $page) {
                    foreach ($page as $element) {
                        if(array_key_exists("data",$element) && is_array($element["data"]) && count($element["data"]) > 0) {
                            foreach($element["data"] as $metaData) {
                                if($metaData["value"] instanceof Element\ElementInterface) {
                                    $tags = $metaData["value"]->getCacheTags($tags);
                                }
                            }
                        }
                    }
                }
                return $tags;
            };

            $tags = $getMetaDataCacheTags($this->hotspots, $tags);
        }


        return $tags;
    }


    /**
     * @return array
     */
    public function resolveDependencies()
    {
        $dependencies = array();

        $asset = Asset::getById($this->id);
        if ($asset instanceof Asset) {
            $key = "asset_" . $asset->getId();
            $dependencies[$key] = array(
                "id" => $asset->getId(),
                "type" => "asset"
            );
        }

        $getMetaDataDependencies = function ($data, $dependencies) {

            if(!is_array($data)) {
                return $dependencies;
            }

            foreach ($data as $page) {
                foreach ($page as $element) {
                    if (array_key_exists("data", $element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach ($element["data"] as $metaData) {
                            if ($metaData["value"] instanceof Element\ElementInterface) {

                                $elTtype = $metaData["type"];
                                if($metaData["type"] == "link") {
                                    $elTtype = "document";
                                }

                                $dependencies[$elTtype . "_" . $metaData["value"]->getId()] = array(
                                    "id" => $metaData["value"]->getId(),
                                    "type" => $elTtype
                                );
                            }
                        }
                    }
                }
            }
            return $dependencies;
        };

        $dependencies = $getMetaDataDependencies($this->hotspots, $dependencies);

        return $dependencies;
    }

    /**
     * @return bool
     */
    public function checkValidity()
    {
        $sane = true;
        if (!empty($this->id)) {
            $el = Asset::getById($this->id);
            if (!$el instanceof Asset) {
                $sane = false;
                \Logger::notice("Detected insane relation, removing reference to non existent asset with id [" . $this->id . "]");
                $this->id = null;
            }
        }

        return $sane;
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

        $rewritePath = function ($data) {

            if(!is_array($data)) {
                return array();
            }

            foreach ($data as &$page) {
                foreach ($page as &$element) {
                    if (array_key_exists("data", $element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach ($element["data"] as &$metaData) {
                            if (in_array($metaData["type"], array("object", "asset", "document", "link"))) {
                                $elTtype = $metaData["type"];
                                if($metaData["type"] == "link") {
                                    $elTtype = "document";
                                }
                                $el = Element\Service::getElementById($elTtype, $metaData["value"]);

                                if(!$el && $metaData["type"] == "link") {
                                    $metaData["value"] = $metaData["value"];
                                } else {
                                    $metaData["value"] = $el;
                                }
                            }
                        }
                    }
                }
            }
            return $data;
        };

        if(array_key_exists("hotspots",$data) && is_array($data["hotspots"]) && count($data["hotspots"]) > 0) {
            $data["hotspots"] = $rewritePath($data["hotspots"]);
        }


        $this->id = $data["id"];
        $this->hotspots = $data["hotspots"];
        $this->texts = $data["texts"];
        $this->chapters = $data["chapters"];

        return $this;
    }

    public function getEditmode()
    {
        return parent::getEditmode(); // TODO: Change the autogenerated stub
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data)
    {
        $pdf = Asset::getById($data["id"]);
        if($pdf instanceof Asset\Document) {
            $this->id = $pdf->getId();
            if(array_key_exists("hotspots", $data) && !empty($data["hotspots"])) {

                $rewritePath = function ($data) {

                    if(!is_array($data)) {
                        return array();
                    }

                    foreach ($data as &$page) {
                        foreach ($page as &$element) {
                            if (array_key_exists("data", $element) && is_array($element["data"]) && count($element["data"]) > 0) {
                                foreach ($element["data"] as &$metaData) {
                                    if (in_array($metaData["type"], array("object", "asset", "document", "link"))) {
                                        $elTtype = $metaData["type"];
                                        if($metaData["type"] == "link") {
                                            $elTtype = "document";
                                        }
                                        $el = Element\Service::getElementByPath($elTtype, $metaData["value"]);

                                        if(!$el && $metaData["type"] == "link") {
                                            $metaData["value"] = $metaData["value"];
                                        } else {
                                            $metaData["value"] = $el;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    return $data;
                };

                if(array_key_exists("hotspots",$data) && is_array($data["hotspots"]) && count($data["hotspots"]) > 0) {
                    $data["hotspots"] = $rewritePath($data["hotspots"]);
                }

                $this->hotspots = $data["hotspots"];
            }
            $this->texts = $data['texts'];
            $this->chapters = $data['chapters'];
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


    public function frontend()
    {
        $asset = Asset::getById($this->id);

        $options = $this->getOptions();


        if ($asset instanceof Asset\Document && $asset->getPageCount()) {
            $pageCount = $asset->getPageCount();
            $hotspots = $this->getHotspots();
            $rewritePath = function ($data) {

                if(!is_array($data)) {
                    return array();
                }

                foreach ($data as &$element) {
                    if(array_key_exists("data",$element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach($element["data"] as &$metaData) {
                            if($metaData["value"] instanceof Document) {
                                $metaData["value"] = $metaData["value"]->getFullPath();
                            }
                        }
                    }
                }

                return $data;
            };

            for($i=1; $i <=$pageCount; $i++) {
                $pageData = array(
                    "thumbnail" => $asset->getImageThumbnail(array(
                        "width" => 200,
                        "height" => 200,
                        "contain" => true,
                        "format" => "pjpeg"
                    ), $i, true),
                    "detail" => $asset->getImageThumbnail(array(
                        "width" => 1500,
                        "height" => 1500,
                        "contain" => true,
                        "quality" => "85",
                        "format" => "pjpeg"
                    ), $i, true)
                );

                if(is_array($hotspots) && $hotspots[$i]) {
                    $pageData["hotspots"] = $rewritePath($hotspots[$i]);
                }

                $data["pages"][] = $pageData;
            }

            $data["pdf"] = $asset->getFullPath();

            $data["fullscreen"] = true;
            if(isset($options["fullscreen"])) {
                $data["fullscreen"] = (bool) $options["fullscreen"];
            }

            $jsVarName = "pimcore_pdf_" . $this->getName();
            $divId = "pimcore-pdf-" . uniqid();
            $jsonData = \Zend_Json::encode($data);

            $code = <<<HTML

            <div id="$divId" class="pimcore-pdfViewer">
                <div class="pimcore-pdfPages"></div>
                <div class="pimcore-pdfZoom">+</div>
                <div class="pimcore-pdfDownload">&#x21e9;</div>
                <div class="pimcore-pdfFullscreenClose">x</div>
                <div class="pimcore-pdfButtonLeft pimcore-pdfButton "><div class="pimcore-pdfArrowLeft"></div></div>
                <div class="pimcore-pdfButtonRight pimcore-pdfButton "><div class="pimcore-pdfArrowRight"></div></div>
            </div>

            <link rel="stylesheet" type="text/css" href="/pimcore/static/js/frontend/pdfViewer/styles.css" />
            <script type="text/javascript" src="/pimcore/static/js/frontend/pdfViewer/viewer.js"></script>
            <script type="text/javascript">
                var $jsVarName = new pimcore.pdf({
                    id: "$divId",
                    data: $jsonData
                });
            </script>
HTML;

            return $code;
        } else {
            return $this->getErrorCode("Asset is not a valid PDF");
        }
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
        <div id="pimcore_pdf_' . $this->getName() . '" class="pimcore_tag_pdf">
            <div class="pimcore_tag_video_error" style="text-align:center; width: ' . $width . '; height: ' . ($this->getHeight()-1) . 'px; border:1px solid #000; background: url(/pimcore/static/img/filetype-not-supported.png) no-repeat center center #fff;">
                ' . $message . '
            </div>
        </div>';

        return $code;
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
     * @param Webservice\Data\Document\Element $wsElement
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $idMapper = null)
    {
        $data = $wsElement->value;
        if($data->id){
            $asset = Asset::getById($data->id);
            if(!$asset){
                throw new \Exception("Referencing unknown asset with id [ ".$data->id." ] in webservice import field [ ".$data->name." ]");
            } else {
                $this->id = $data->id;
            }
        }
    }

    /**
     * @param array $texts
     */
    public function setTexts($texts){
        $this->texts = (array)$texts;
    }

    /**
     * @return array
     */
    public function getTexts(){
        return (array)$this->texts;
    }

    public function getElement(){
        $data = $this->getData();
        return Asset::getById($data['id']);
    }

    public function getText($page){
        $texts = $this->getTexts();
        if($texts[$page]){
            return $texts[$page];
        }else{
            $asset = $this->getElement();
            if($asset instanceof Asset\Document){
                return $asset->getText($page);
            }
        }
    }

    public function setChapters($chapters){
        $this->chapters = (array)$chapters;
    }

    public function getChapters(){
        return (array)$this->chapters;
    }

    public function getChapter($page){
        $chapters = $this->getChapters();
        return $chapters[$page];
    }

    /**
     * @param array $hotspots
     */
    public function setHotspots($hotspots)
    {
        $this->hotspots = $hotspots;
    }

    /**
     * @return array
     */
    public function getHotspots()
    {
        return (array)$this->hotspots;
    }


    public function getHotspot($page){
        $hotspots = $this->getHotspots();
        return $hotspots[$page];
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)  $this->id;
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
        if(array_key_exists("asset", $idMapping) and array_key_exists($this->getId(), $idMapping["asset"])) {
            $this->setId($idMapping["asset"][$this->getId()]);
        }
    }
}
