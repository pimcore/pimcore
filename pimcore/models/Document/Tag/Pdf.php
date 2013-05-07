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

class Document_Tag_Pdf extends Document_Tag
{
    /**
     * @var int
     */
    public $id;

    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType()
    {
        return "pdf";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData()
    {
        return array(
            "id" => $this->id
        );
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
                Logger::notice("Detected insane relation, removing reference to non existent asset with id [" . $this->id . "]");
                $this->id = null;
            }
        }

        return $sane;
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

        return $this;
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data)
    {
        $pdf = Asset::getById($data["id"]);
        if($pdf instanceof Asset_Document) {
            $this->id = $pdf->getId();
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

        if ($asset instanceof Asset_Document) {
            return "PDF";
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
        if(!Pimcore::inDebugMode()) {
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
     * @param Webservice_Data_Document_Element $wsElement
     * @param null $idMapper
     * @throws Exception
     */
    public function getFromWebserviceImport($wsElement, $idMapper = null)
    {
        $data = $wsElement->value;
        if($data->id){
            $asset = Asset::getById($data->id);
            if(!$asset){
                throw new Exception("Referencing unknown asset with id [ ".$data->id." ] in webservice import field [ ".$data->name." ]");
            } else {
                $this->id = $data->id;
            }
        }
    }
}
