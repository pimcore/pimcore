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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Google\Cse;

class Item {

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
     * @return $this
     */
    public function setValues($data = array()) {
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $key => $value) {
                $this->setValue($key,$value);
            }
        }
        return $this;
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
        return $this;
    }

    /**
     * @param $displayLink
     * @return $this
     */
    public function setDisplayLink($displayLink)
    {
        $this->displayLink = $displayLink;
        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayLink()
    {
        return $this->displayLink;
    }

    /**
     * @param $document
     * @return $this
     */
    public function setDocument($document)
    {
        $this->document = $document;
        return $this;
    }

    /**
     * @return string
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param $formattedUrl
     * @return $this
     */
    public function setFormattedUrl($formattedUrl)
    {
        $this->formattedUrl = $formattedUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormattedUrl()
    {
        return $this->formattedUrl;
    }

    /**
     * @param $htmlFormattedUrl
     * @return $this
     */
    public function setHtmlFormattedUrl($htmlFormattedUrl)
    {
        $this->htmlFormattedUrl = $htmlFormattedUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlFormattedUrl()
    {
        return $this->htmlFormattedUrl;
    }

    /**
     * @param $htmlSnippet
     * @return $this
     */
    public function setHtmlSnippet($htmlSnippet)
    {
        $this->htmlSnippet = $htmlSnippet;
        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlSnippet()
    {
        return $this->htmlSnippet;
    }

    /**
     * @param $htmlTitle
     * @return $this
     */
    public function setHtmlTitle($htmlTitle)
    {
        $this->htmlTitle = $htmlTitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlTitle()
    {
        return $this->htmlTitle;
    }

    /**
     * @param $image
     * @return $this
     */
    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param $link
     * @return $this
     */
    public function setLink($link)
    {
        $this->link = $link;
        return $this;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param $raw
     * @return $this
     */
    public function setRaw($raw)
    {
        $this->raw = $raw;
        return $this;
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * @param $snippet
     * @return $this
     */
    public function setSnippet($snippet)
    {
        $this->snippet = $snippet;
        return $this;
    }

    /**
     * @return string
     */
    public function getSnippet()
    {
        return $this->snippet;
    }

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
        return $this->title;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
