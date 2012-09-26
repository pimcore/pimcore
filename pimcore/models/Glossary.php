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
 * @package    Glossary
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Glossary extends Pimcore_Model_Abstract {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $text;

    /**
     * @var string
     */
    public $link;

    /**
     * @var string
     */
    public $abbr;

    /**
     * @var string
     */
    public $acronym;

    /**
     * @var string
     */
    public $language;

    /**
     * @var bool
     */
    public $casesensitive;

    /**
     * @var bool
     */
    public $exactmatch;

    /**
     * @var int
     */
    public $site;

    /**
     * @param integer $id
     * @return Glossary
     */
    public static function getById($id) {

        $glossary = new self();
        $glossary->setId(intval($id));
        $glossary->getResource()->getById();

        return $glossary;
    }

    /**
     * @return Glossary
     */
    public static function create() {
        $glossary = new self();
        $glossary->save();

        return $glossary;
    }


    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }


    /**
     * @param string $text
     * @return void
     */
    public function setText($text) {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getText() {
        return $this->text;
    }

    /**
     * @param string $link
     * @return void
     */
    public function setLink($link) {
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getLink() {
        return $this->link;
    }


    /**
     * @param string $abbr
     * @return void
     */
    public function setAbbr($abbr) {
        $this->abbr = $abbr;
    }

    /**
     * @return string
     */
    public function getAbbr() {
        return $this->abbr;
    }


    /**
     * @param string $acronym
     * @return void
     */
    public function setAcronym($acronym) {
        $this->acronym = $acronym;
    }

    /**
     * @return string
     */
    public function getAcronym() {
        return $this->acronym;
    }


    /**
     * @param string $language
     * @return void
     */
    public function setLanguage($language) {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param boolean $casesensitive
     */
    public function setCasesensitive($casesensitive)
    {
        $this->casesensitive = (bool) $casesensitive;
    }

    /**
     * @return boolean
     */
    public function getCasesensitive()
    {
        return $this->casesensitive;
    }

    /**
     * @param boolean $exactmatch
     */
    public function setExactmatch($exactmatch)
    {
        $this->exactmatch = (bool) $exactmatch;
    }

    /**
     * @return boolean
     */
    public function getExactmatch()
    {
        return $this->exactmatch;
    }

    /**
     * @param int $site
     */
    public function setSite($site)
    {
        if($site instanceof Site) {
            $site = $site->getId();
        }
        $this->site = (int) $site;
    }

    /**
     * @return int
     */
    public function getSite()
    {
        return $this->site;
    }
}
