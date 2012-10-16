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

class Document_Page extends Document_PageSnippet {


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
     * @see Document::delete and Document_PageSnippet::delete
     * @return void
     */
    public function delete() {
        if ($this->getId() == 1) {
            throw new Exception("root-node cannot be deleted");
        }

        // check for redirects pointing to this document, and delete them too
        $redirects = new Redirect_List();
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

        parent::update();

        $config = Pimcore_Config::getSystemConfig();
        if ($this->_oldPath && $config->documents->createredirectwhenmoved) {
            // create redirect for old path
            $redirect = new Redirect();
            $redirect->setTarget($this->getId());
            $redirect->setSource("@" . $this->_oldPath . "/?@");
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
        return Pimcore_Tool_Text::removeLineBreaks($this->title);
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription($description) {
        $this->description = str_replace("\n"," ",$description);
    }

    /**
     * @param string $keywords
     * @return void
     */
    public function setKeywords($keywords) {
        $this->keywords = str_replace("\n"," ",$keywords);
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     *
     */
    public function getFullPath() {

        $path = parent::getFullPath();

        // do not use pretty url's when in admin, the current document is wrapped by a hardlink or this document isn't in the current site
        if(!Pimcore::inAdmin() && !($this instanceof Document_Hardlink_Wrapper_Interface) && Pimcore_Tool_Frontend::isDocumentInCurrentSite($this)) {
            // check for a pretty url
            $prettyUrl = $this->getPrettyUrl();
            if(!empty($prettyUrl) && strlen($prettyUrl) > 1) {
                return $prettyUrl;
            }
        }

        return $path;
    }

    /**
     * @param string $prettyUrl
     */
    public function setPrettyUrl($prettyUrl)
    {
        $this->prettyUrl = rtrim($prettyUrl, " /");
    }

    /**
     * @return string
     */
    public function getPrettyUrl()
    {
        return $this->prettyUrl;
    }
}
