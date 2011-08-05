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
     * @see Document::delete and Document_PageSnippet::delete
     * @return void
     */
    public function delete() {
        if ($this->getId() == 1) {
            throw new Exception("root-node cannot be deleted");
        }

        parent::delete();
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
}
