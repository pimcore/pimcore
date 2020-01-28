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

namespace Pimcore\Translation\AttributeSet;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Translation\TranslationItemCollection\TranslationItem;

class AttributeSet
{
    /**
     * @var TranslationItem
     */
    private $translationItem;

    /**
     * @var string
     */
    private $sourceLanguage = '';

    /**
     * @var string[]
     */
    private $targetLanguages = [];

    /**
     * @var Attribute[];
     */
    private $attributes = [];

    /**
     * DataExtractorResult constructor.
     *
     * @param TranslationItem $translationItem
     */
    public function __construct(TranslationItem $translationItem)
    {
        $this->translationItem = $translationItem;
    }

    /**
     * @return TranslationItem
     */
    public function getTranslationItem(): TranslationItem
    {
        return $this->translationItem;
    }

    /**
     * @param ElementInterface $translationItem
     *
     * @return AttributeSet
     */
    public function setTranslationItem(ElementInterface $translationItem): AttributeSet
    {
        $this->translationItem = $translationItem;

        return $this;
    }

    /**
     * @return string
     */
    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    /**
     * @param string $sourceLanguage
     *
     * @return AttributeSet
     */
    public function setSourceLanguage(string $sourceLanguage): AttributeSet
    {
        $this->sourceLanguage = $sourceLanguage;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTargetLanguages(): array
    {
        return $this->targetLanguages;
    }

    /**
     * @param string[] $targetLanguages
     *
     * @return AttributeSet
     */
    public function setTargetLanguages(array $targetLanguages): AttributeSet
    {
        $this->targetLanguages = $targetLanguages;

        return $this;
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isEmpty(): bool
    {
        if (empty($this->attributes)) {
            return true;
        }

        foreach ($this->attributes as $attribute) {
            if (!$attribute->isReadonly()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $type
     * @param string $name
     * @param string $content
     * @param bool $isReadonly
     * @param string[] $targetContent
     *
     * @return AttributeSet
     */
    public function addAttribute(string $type, string $name, string $content, bool $isReadonly = false, array $targetContent = []): AttributeSet
    {
        $this->attributes[] = new Attribute($type, $name, $content, $isReadonly, $targetContent);

        return $this;
    }
}
