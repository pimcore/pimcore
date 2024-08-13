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

namespace Pimcore\Bundle\XliffBundle\AttributeSet;

use Pimcore\Bundle\XliffBundle\TranslationItemCollection\TranslationItem;

class AttributeSet
{
    private TranslationItem $translationItem;

    private string $sourceLanguage = '';

    /**
     * @var string[]
     */
    private array $targetLanguages = [];

    /**
     * @var Attribute[];
     */
    private array $attributes = [];

    /**
     * DataExtractorResult constructor.
     *
     */
    public function __construct(TranslationItem $translationItem)
    {
        $this->translationItem = $translationItem;
    }

    public function getTranslationItem(): TranslationItem
    {
        return $this->translationItem;
    }

    public function setTranslationItem(TranslationItem $translationItem): AttributeSet
    {
        $this->translationItem = $translationItem;

        return $this;
    }

    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

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
     * @param string[] $targetContent
     *
     */
    public function addAttribute(string $type, string $name, string $content, bool $isReadonly = false, array $targetContent = []): AttributeSet
    {
        $this->attributes[] = new Attribute($type, $name, $content, $isReadonly, $targetContent);

        return $this;
    }
}
