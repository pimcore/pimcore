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

namespace Pimcore\Model\Document;

use Pimcore\Model;
use Pimcore\Model\Redirect;

class Page extends Model\Document\PageSnippet {


    /**
     * Contains the title of the page (meta-title)
     *
     * @var string
     */
    public $title = "";

    /**
     * Contains the description of the page (meta-description)
     *
     * @var string
     */
    public $description = "";

    /**
     * Contains the keywords of the page (meta-keywords)
     *
     * @var string
     */
    public $keywords = "";

    /**
     * @var array
     */
    public $metaData = array();

    /**
     * Static type of the document
     *
     * @var string
     */
    public $type = "page";

    /**
     * @var string
     */
    public $prettyUrl;

    /**
     * @var string
     */
    public $css = "";

    /**
     * comma separated IDs of personas
     * @var string
     */
    public $personas = "";

    /**
     * @var int
     */
    public $usePersona;

    /**
     * @throws \Exception
     */
    public function delete() {
        if ($this->getId() == 1) {
            throw new \Exception("root-node cannot be deleted");
        }

        // check for redirects pointing to this document, and delete them too
        $redirects = new Redirect\Listing();
        $redirects->setCondition("target = ?", $this->getId());
        $redirects->load();

        foreach($redirects->getRedirects() as $redirect) {
            $redirect->delete();
        }

        parent::delete();
    }

    /**
     *
     */
    protected function update() {

        $oldPath = $this->getResource()->getCurrentFullPath();

        parent::update();

        $config = \Pimcore\Config::getSystemConfig();
        if ($oldPath && $config->documents->createredirectwhenmoved && $oldPath != $this->getFullPath()) {
            // create redirect for old path
            $redirect = new Redirect();
            $redirect->setTarget($this->getId());
            $redirect->setSource("@" . $oldPath . "/?@");
            $redirect->setStatusCode(301);
            $redirect->setExpiry(time() + 86400 * 60); // this entry is removed automatically after 60 days
            $redirect->save();
        }
    }

    /**
     * getProperty method should be used instead
     *
     * @deprecated
     * @return string
     */
    public function getName() {
        return $this->getProperty("navigation_name");
    }

    /**
     * setProperty method should be used instead
     *
     * @deprecated
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->setProperty("navigation_name","text",$name,false);
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getKeywords() {
        return $this->keywords;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return \Pimcore\Tool\Text::removeLineBreaks($this->title);
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription($description) {
        $this->description = str_replace("\n"," ",$description);
        return $this;
    }

    /**
     * @param string $keywords
     * @return void
     */
    public function setKeywords($keywords) {
        $this->keywords = str_replace("\n"," ",$keywords);
        return $this;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * @param $metaData
     * @return $this
     */
    public function setMetaData($metaData)
    {
        $this->metaData = $metaData;
        return $this;
    }

    /**
     * @return array
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     *
     */
    public function getFullPath() {

        $path = parent::getFullPath();

        // do not use pretty url's when in admin, the current document is wrapped by a hardlink or this document isn't in the current site
        if(!\Pimcore::inAdmin() && !($this instanceof Hardlink\Wrapper\WrapperInterface) && \Pimcore\Tool\Frontend::isDocumentInCurrentSite($this)) {
            // check for a pretty url
            $prettyUrl = $this->getPrettyUrl();
            if(!empty($prettyUrl) && strlen($prettyUrl) > 1) {
                return $prettyUrl;
            }
        }

        return $path;
    }

    /**
     * @param $prettyUrl
     * @return $this
     */
    public function setPrettyUrl($prettyUrl)
    {
        $this->prettyUrl = "/" . trim($prettyUrl, " /");
        if(strlen($this->prettyUrl) < 2) {
            $this->prettyUrl = null;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getPrettyUrl()
    {
        return $this->prettyUrl;
    }

    /**
     * @param $css
     * @return $this
     */
    public function setCss($css)
    {
        $this->css = $css;
        return $this;
    }

    /**
     * @return string
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @param string $personas
     */
    public function setPersonas($personas)
    {
        $personas = trim($personas, " ,");
        if(!empty($personas)) {
            $personas = "," . $personas . ",";
        }
        $this->personas = $personas;
    }

    /**
     * @return string
     */
    public function getPersonas()
    {
        return $this->personas;
    }

    /**
     * @param null $personaId
     * @return null|string
     */
    public function getPersonaElementPrefix($personaId = null) {
        $prefix = null;

        if(!$personaId) {
            $personaId = $this->getUsePersona();
        }

        if($personaId) {
            $prefix = "persona_-" . $personaId . "-_";
        }

        return $prefix;
    }

    /**
     * @param $name
     * @return string
     */
    public function getPersonaElementName($name) {
        if($this->getUsePersona() && !preg_match("/^" . preg_quote($this->getPersonaElementPrefix(),"/") . "/", $name)) {
            $name = $this->getPersonaElementPrefix() . $name;
        }
        return $name;
    }

    /**
     * @param string $name
     * @param string $data
     */
    public function setElement($name, $data) {

        if($this->getUsePersona()) {
            $name = $this->getPersonaElementName($name);
            $data->setName($name);
        }

        return parent::setElement($name, $data);
    }

    /**
     * @param string $name
     * @return Model\Document\Tag
     */
    public function getElement($name) {

        // check if a persona is requested for this page, if yes deliver a different version of the element (prefixed)
        if($this->getUsePersona()) {
            $personaName = $this->getPersonaElementName($name);

            if($this->hasElement($personaName)) {
                $name = $personaName;
            } else {
                // if there's no dedicated content for this persona, inherit from the "original" content (unprefixed)
                // and mark it as inherited so it is clear in the ui that the content is not specific to the selected persona
                // replace all occurrences of the persona prefix, this is needed because of block-prefixes
                $inheritedName = str_replace($this->getPersonaElementPrefix(), "", $name);
                $inheritedElement = parent::getElement($inheritedName);
                if($inheritedElement) {
                    $inheritedElement = clone $inheritedElement;
                    $inheritedElement->setResource(null);
                    $inheritedElement->setName($personaName);
                    $inheritedElement->setInherited(true);
                    $this->setElement($personaName, $inheritedElement);
                    return $inheritedElement;
                }
            }
        }

        // delegate to default
        return parent::getElement($name);
    }

    /**
     * @param int $usePersona
     */
    public function setUsePersona($usePersona)
    {
        $this->usePersona = $usePersona;
    }

    /**
     * @return int
     */
    public function getUsePersona()
    {
        return $this->usePersona;
    }

    /**
     *
     */
    public function __sleep() {

        $finalVars = array();
        $parentVars = parent::__sleep();

        $blockedVars = array("usePersona");

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }
}
