<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Google\Cse;

use Google\Service\CustomSearchAPI\Result;
use Pimcore\Model;

class Item
{
    public Result $raw;

    public string $title;

    public string $htmlTitle;

    public string $link;

    public string $displayLink;

    public string $snippet;

    public string $htmlSnippet;

    public string $formattedUrl;

    public string $htmlFormattedUrl;

    public string|Model\Asset\Image|null $image;

    public ?Model\Document $document = null;

    public string $type;

    public function __construct(Result $data)
    {
        $this->setRaw($data);
        $this->setValues($data);
    }

    public function setValues(Result $data): static
    {
        $properties = get_object_vars($data);
        foreach ($properties as $key => $value) {
            $this->setValue($key, $value);
        }

        return $this;
    }

    public function setValue(string $key, mixed $value): static
    {
        $method = 'set' . $key;
        if (method_exists($this, $method)) {
            $this->$method($value);
        }

        return $this;
    }

    public function setDisplayLink(string $displayLink): static
    {
        $this->displayLink = $displayLink;

        return $this;
    }

    public function getDisplayLink(): string
    {
        return $this->displayLink;
    }

    public function setDocument(Model\Document $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getDocument(): ?Model\Document
    {
        return $this->document;
    }

    public function setFormattedUrl(string $formattedUrl): static
    {
        $this->formattedUrl = $formattedUrl;

        return $this;
    }

    public function getFormattedUrl(): string
    {
        return $this->formattedUrl;
    }

    public function setHtmlFormattedUrl(string $htmlFormattedUrl): static
    {
        $this->htmlFormattedUrl = $htmlFormattedUrl;

        return $this;
    }

    public function getHtmlFormattedUrl(): string
    {
        return $this->htmlFormattedUrl;
    }

    public function setHtmlSnippet(string $htmlSnippet): static
    {
        $this->htmlSnippet = $htmlSnippet;

        return $this;
    }

    public function getHtmlSnippet(): string
    {
        return $this->htmlSnippet;
    }

    public function setHtmlTitle(string $htmlTitle): static
    {
        $this->htmlTitle = $htmlTitle;

        return $this;
    }

    public function getHtmlTitle(): string
    {
        return $this->htmlTitle;
    }

    public function setImage(Model\Asset\Image|string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getImage(): Model\Asset\Image|string|null
    {
        return $this->image;
    }

    public function setLink(string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    /**+
     * @param Result $raw
     * @return $this
     */
    public function setRaw(Result $raw): static
    {
        $this->raw = $raw;

        return $this;
    }

    public function getRaw(): Result
    {
        return $this->raw;
    }

    public function setSnippet(string $snippet): static
    {
        $this->snippet = $snippet;

        return $this;
    }

    public function getSnippet(): string
    {
        return $this->snippet;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
