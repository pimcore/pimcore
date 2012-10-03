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

class Document_Tag_Link extends Document_Tag
{

    /**
     * Contains the data for the link
     *
     * @var array
     */
    public $data;

    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType()
    {
        return "link";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return string
     */
    public function frontend()
    {

        $url = $this->getHref();

        if (strlen($url) > 0) {
            // add attributes to link
            $attribs = array();
            if (is_array($this->options)) {
                foreach ($this->options as $key => $value) {
                    if (is_string($value) || is_numeric($value)) {
                        $attribs[] = $key . '="' . $value . '"';
                    }
                }
            }
            // add attributes to link
            $allowedAttributes = array("charset", "coords", "hreflang", "name", "rel", "rev", "shape", "target", "accesskey", "class", "dir", "id", "lang", "style", "tabindex", "title", "xml:lang", "onblur", "onclick", "ondblclick", "onfocus", "onmousedown", "onmousemove", "onmouseout", "onmouseover", "onmouseup", "onkeydown", "onkeypress", "onkeyup");
            $defaultAttributes = array();

            if (!is_array($this->options)) {
                $this->options = array();
            }
            if (!is_array($this->data)) {
                $this->data = array();
            }

            $availableAttribs = array_merge($defaultAttributes, $this->data, $this->options);

            foreach ($availableAttribs as $key => $value) {
                if ((is_string($value) || is_numeric($value)) && in_array($key, $allowedAttributes)) {
                    if (!empty($value)) {
                        $attribs[] = $key . '="' . $value . '"';
                    }
                }
            }

            $attribs = array_unique($attribs);

            if (array_key_exists("attributes", $this->data) && !empty($this->data["attributes"])) {
                $attribs[] = $this->data["attributes"];
            }

            return '<a href="' . $url . '" ' . implode(" ", $attribs) . '>' . htmlspecialchars($this->data["text"]) . '</a>';
        }
        return "";
    }

    /**
     * @return bool
     */
    public function checkValidity()
    {
        $sane = true;
        if ($this->data["internal"]) {
            if ($this->data["internalType"] == "document") {
                $doc = Document::getById($this->data["internalId"]);
                if (!$doc) {
                    $sane = false;
                    Logger::notice("Detected insane relation, removing reference to non existent document with id [" . $this->getDocumentId() . "]");
                    $new = Document_Tag::factory($this->getType(), $this->getName(), $this->getDocumentId());
                    $this->data = $new->getData();
                }
            } else if ($this->data["internalType"] == "asset") {
                $asset = Asset::getById($this->data["internalId"]);
                if (!$asset) {
                    $sane = false;
                    Logger::notice("Detected insane relation, removing reference to non existent asset with id [" . $this->getDocumentId() . "]");
                    $new = Document_Tag::factory($this->getType(), $this->getName(), $this->getDocumentId());
                    $this->data = $new->getData();
                }
            }
        }
        return $sane;
    }


    /**
     * @return string
     */
    public function getHref()
    {

        if ($this->data["internal"]) {
            if ($this->data["internalType"] == "document") {
                if ($doc = Document::getById($this->data["internalId"])) {
                    if (!Document::doHideUnpublished() || $doc->isPublished()) {
                        $this->data["path"] = $doc->getFullPath();
                    } else {
                        $this->data["path"] = "";
                    }
                }
            } else if ($this->data["internalType"] == "asset") {
                if ($asset = Asset::getById($this->data["internalId"])) {
                    $this->data["path"] = $asset->getFullPath();
                }
            }
        }

        $url = $this->data["path"];

        if (strlen($this->data["parameters"]) > 0) {
            $url .= "?" . str_replace("?", "", $this->getParameters());
        }

        if (strlen($this->data["anchor"]) > 0) {
            $url .= "#" . str_replace("#", "", $this->getAnchor());
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->data["text"];
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->data["target"];
    }

    /**
     * @return string
     */
    public function getParameters()
    {
        return $this->data["parameters"];
    }

    /**
     * @return string
     */
    public function getAnchor()
    {
        return $this->data["anchor"];
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->data["title"];
    }

    /**
     * @return string
     */
    public function getRel()
    {
        return $this->data["rel"];
    }

    /**
     * @return string
     */
    public function getTabindex()
    {
        return $this->data["tabindex"];
    }

    /**
     * @return string
     */
    public function getAccesskey()
    {
        return $this->data["accesskey"];
    }


    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data)
    {
        $this->data = Pimcore_Tool_Serialize::unserialize($data);
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data)
    {

        if ($doc = Document::getByPath($data["path"])) {

            if ($doc instanceof Document) {
                $data["internal"] = true;
                $data["internalId"] = $doc->getId();
                $data["internalType"] = "document";
            }
        }

        if (!$data["internal"]) {
            if ($asset = Asset::getByPath($data["path"])) {
                if ($asset instanceof Asset) {
                    $data["internal"] = true;
                    $data["internalId"] = $asset->getId();
                    $data["internalType"] = "asset";
                }
            }
        }

        $this->data = $data;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return (strlen($this->getHref()) < 1);
    }


    /**
     * This is a dummy and is mostly implemented by relation types
     * @param $ownerDocument
     * @param array $blockedTags
     */
    /*public function getCacheTags($ownerDocument, $blockedTags = array()) {

        $tags = array();

        if ($this->data["internal"]) {
            if ($this->data["internalType"] == "document") {
                if ($doc = Document::getById($this->data["internalId"])) {
                    if ($doc->getId() != $ownerDocument->getId() and !in_array($doc->getCacheTag(), $blockedTags)) {
                        $tags = array_merge($tags, $doc->getCacheTags($blockedTags));
                    }
                }
            }
            else if ($this->data["internalType"] == "asset") {
                if ($asset = Asset::getById($this->data["internalId"])) {
                    $tags = array_merge($tags, $asset->getCacheTags($blockedTags));
                }
            }
        }

        return $tags;
    }*/

    /**
     * @return array
     */
    public function resolveDependencies()
    {

        $dependencies = array();

        if ($this->data["internal"]) {

            if (intval($this->data["internalId"]) > 0) {
                if ($this->data["internalType"] == "document") {
                    if ($doc = Document::getById($this->data["internalId"])) {

                        $key = "document_" . $doc->getId();

                        $dependencies[$key] = array(
                            "id" => $doc->getId(),
                            "type" => "document"
                        );
                    }
                } else if ($this->data["internalType"] == "asset") {
                    if ($asset = Asset::getById($this->data["internalId"])) {
                        $key = "asset_" . $asset->getId();

                        $dependencies[$key] = array(
                            "id" => $asset->getId(),
                            "type" => "asset"
                        );
                    }
                }
            }
        }
        return $dependencies;
    }

    /**
     * Receives a Webservice_Data_Document_Element from webservice import and fill the current tag's data
     *
     * @abstract
     * @param  Webservice_Data_Document_Element $wsElement
     * @return void
     */
    public function getFromWebserviceImport($wsElement)
    {

        if (empty($wsElement->value->data) or is_array($wsElement->value->data)) {

            $this->data = $wsElement->value->data;
            if ($this->data["internal"]) {
                if (intval($this->data["internalId"]) > 0) {
                    if ($this->data["internalType"] == "document") {
                        $referencedDocument = Document::getById($this->data["internalId"]);
                        if (!$referencedDocument instanceof Document) {
                            throw new Exception("cannot get values from web service import - link references unknown document with id [ " . $this->data["internalId"] . " ] ");
                        }
                    } else if ($this->data["internalType"] == "asset") {
                        $referencedAsset = Asset::getById($this->data["internalId"]);
                        if (!$referencedAsset instanceof Asset) {
                            throw new Exception("cannot get values from web service import - link references unknown asset with id [ " . $this->data["internalId"] . " ] ");
                        }
                    }
                }
            }

        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }

    }


    /**
     * Returns the current tag's data for web service export
     *
     * @abstract
     * @return array
     */
    public function getForWebserviceExport()
    {
        $el = parent::getForWebserviceExport();
        if ($this->data["internal"]) {
            if (intval($this->data["internalId"]) > 0) {
                if ($this->data["internalType"] == "document") {
                    $referencedDocument = Document::getById($this->data["internalId"]);
                    if (!$referencedDocument instanceof Document) {
                        //detected broken link
                        $document = Document::getById($this->getDocumentId());
                    }
                } else if ($this->data["internalType"] == "asset") {
                    $referencedAsset = Asset::getById($this->data["internalId"]);
                    if (!$referencedAsset instanceof Asset) {
                        //detected broken link
                        $document = Document::getById($this->getDocumentId());
                    }
                }
            }
        }

        $el->data = $this->data;
        return $el;
    }


    /**
     * Rewrites id from source to target, $idMapping contains sourceId => targetId mapping
     * @param array $idMapping
     * @return void
     */
    public function rewriteIds($idMapping)
    {
        if ($this->data["internal"]) {
            if ($this->data["internalType"] == "document") {
                if (array_key_exists((int)$this->data["internalId"], $idMapping)) {
                    $this->data["internalId"] = $idMapping[(int)$this->data["internalId"]];
                    $this->getHref();
                }
            }
        }
    }


}
