<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Google\Cse;

use Pimcore\Model;

class Item
{
    /**
     * @var \Google_Service_Customsearch_Result
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
     * @var Model\Asset\Image|string|null
     */
    public $image;

    /**
     * @var Model\Document|null
     */
    public $document;

    /**
     * @var string
     */
    public $type;

    /**
     * @param \Google_Service_Customsearch_Result $data
     */
    public function __construct(\Google_Service_Customsearch_Result $data)
    {
        $this->setRaw($data);
        $this->setValues($data);
    }

    /**
     * @param \Google_Service_Customsearch_Result $data
     *
     * @return $this
     */
    public function setValues(\Google_Service_Customsearch_Result $data)
    {
        $properties = get_object_vars($data);
        foreach ($properties as $key => $value) {
            $this->setValue($key, $value);
        }

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($key, $value)
    {
        $method = 'set' . $key;
        if (method_exists($this, $method)) {
            $this->$method($value);
        }

        return $this;
    }

    /**
     * @param string $displayLink
     *
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
     * @param Model\Document $document
     *
     * @return $this
     */
    public function setDocument($document)
    {
        $this->document = $document;

        return $this;
    }

    /**
     * @return Model\Document|null
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param string $formattedUrl
     *
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
     * @param string $htmlFormattedUrl
     *
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
     * @param string $htmlSnippet
     *
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
     * @param string $htmlTitle
     *
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
     * @param Model\Asset\Image|string $image
     *
     * @return $this
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Model\Asset\Image|string|null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $link
     *
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

    /**+
     * @param \Google_Service_Customsearch_Result $raw
     * @return $this
     */
    public function setRaw(\Google_Service_Customsearch_Result $raw)
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * @return \Google_Service_Customsearch_Result
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * @param string $snippet
     *
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
     * @param string $title
     *
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
     * @param string $type
     *
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
