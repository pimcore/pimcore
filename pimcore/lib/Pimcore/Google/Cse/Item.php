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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Google_Cse_Item {

    /**
     * @var array
     */
    public $raw;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $htmlTitle;

    /**
     * @var string
     */
    public $link;

    /**
     * @var string
     */
    public $displayLink;

    /**
     * @var string
     */
    public $snippet;

    /**
     * @var string
     */
    public $htmlSnippet;

    /**
     * @var string
     */
    public $formattedUrl;

    /**
     * @var string
     */
    public $htmlFormattedUrl;

    /**
     * @var string
     */
    public $image;

    /**
     * @var string
     */
    public $document;

    /**
     * @var string
     */
    public $type;


    /**
     * @param array $data
     */
    public function __construct($data) {

        $this->setRaw($data);
        $this->setValues($data);
    }

    /**
     * @param array $data
     */
    public function setValues($data = array()) {
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $key => $value) {
                $this->setValue($key,$value);
            }
        }
    }

    /**
     * @param  $key
     * @param  $value
     * @return void
     */
    public function setValue($key, $value) {
        $method = "set" . $key;
        if (method_exists($this, $method)) {
            $this->$method($value);
        }
    }

    /**
     * @param string $displayLink
     */
    public function setDisplayLink($displayLink)
    {
        $this->displayLink = $displayLink;
    }

    /**
     * @return string
     */
    public function getDisplayLink()
    {
        return $this->displayLink;
    }

    /**
     * @param string $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return string
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param string $formattedUrl
     */
    public function setFormattedUrl($formattedUrl)
    {
        $this->formattedUrl = $formattedUrl;
    }

    /**
     * @return string
     */
    public function getFormattedUrl()
    {
        return $this->formattedUrl;
    }

    /**
     * @param string $htmlFormattedUrl
     */
    public function setHtmlFormattedUrl($htmlFormattedUrl)
    {
        $this->htmlFormattedUrl = $htmlFormattedUrl;
    }

    /**
     * @return string
     */
    public function getHtmlFormattedUrl()
    {
        return $this->htmlFormattedUrl;
    }

    /**
     * @param string $htmlSnippet
     */
    public function setHtmlSnippet($htmlSnippet)
    {
        $this->htmlSnippet = $htmlSnippet;
    }

    /**
     * @return string
     */
    public function getHtmlSnippet()
    {
        return $this->htmlSnippet;
    }

    /**
     * @param string $htmlTitle
     */
    public function setHtmlTitle($htmlTitle)
    {
        $this->htmlTitle = $htmlTitle;
    }

    /**
     * @return string
     */
    public function getHtmlTitle()
    {
        return $this->htmlTitle;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param array $raw
     */
    public function setRaw($raw)
    {
        $this->raw = $raw;
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * @param string $snippet
     */
    public function setSnippet($snippet)
    {
        $this->snippet = $snippet;
    }

    /**
     * @return string
     */
    public function getSnippet()
    {
        return $this->snippet;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }


}
