<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Translation\ExportDataExtractorService\DataExtractor;

use Pimcore\Document\Editable\EditableUsageResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Property;
use Pimcore\Translation\AttributeSet\Attribute;
use Pimcore\Translation\AttributeSet\AttributeSet;
use Pimcore\Translation\TranslationItemCollection\TranslationItem;

class DocumentDataExtractor extends AbstractElementDataExtractor
{
    const EXPORTABLE_TAGS = ['wysiwyg', 'input', 'textarea', 'image', 'link'];

    /**
     * @var EditableUsageResolver
     */
    private $EditableUsageResolver;

    public function __construct(EditableUsageResolver $EditableUsageResolver)
    {
        $this->EditableUsageResolver = $EditableUsageResolver;
    }

    /**
     * @param TranslationItem $translationItem
     * @param string $sourceLanguage
     * @param string[] $targetLanguages
     *
     * @return AttributeSet
     *
     * @throws \Exception
     */
    public function extract(TranslationItem $translationItem, string $sourceLanguage, array $targetLanguages): AttributeSet
    {
        $document = $translationItem->getElement();

        $result = parent::extract($translationItem, $sourceLanguage, $targetLanguages);

        if (!$document instanceof Document) {
            throw new \Exception('only documents allowed');
        }

        $this
            ->addDoumentEditables($document, $result)
            ->addSettings($document, $result);

        return $result;
    }

    /**
     * @param Document $document
     * @param AttributeSet $result
     *
     * @return DocumentDataExtractor
     *
     * @throws \Exception
     */
    protected function addDoumentEditables(Document $document, AttributeSet $result): DocumentDataExtractor
    {
        $editables = [];
        $service = new Document\Service;

        $translations = $service->getTranslations($document);

        if ($document instanceof Document\PageSnippet) {
            $editableNames = $this->EditableUsageResolver->getUsedEditableNames($document);
            foreach ($editableNames as $editableName) {
                if ($editable = $document->getEditable($editableName)) {
                    $editables[] = $editable;
                }
            }
        }

        foreach ($editables as $editable) {
            if (in_array($editable->getType(), self::EXPORTABLE_TAGS)) {
                if ($editable instanceof Document\Editable\Image || $editable instanceof Document\Editable\Link) {
                    $content = $editable->getText();
                } else {
                    $content = $editable->getData();
                }

                $targetContent = [];
                foreach ($result->getTargetLanguages() as $targetLanguage) {
                    if (isset($translations[$targetLanguage])) {
                        $targetDocument = Document::getById($translations[$targetLanguage]);

                        if ($targetDocument instanceof  Document\PageSnippet) {
                            $targetTag = $targetDocument->getEditable($editable->getName());
                            if ($targetTag instanceof Document\Editable\Image || $targetTag instanceof Document\Editable\Link) {
                                $targetContent[$targetLanguage] = $targetTag->getText();
                            } elseif ($targetTag !== null) {
                                $targetContent[$targetLanguage] = $targetTag->getData();
                            }
                        }
                    }
                }

                if (is_string($content)) {
                    $contentCheck = trim(strip_tags($content));
                    if (!empty($contentCheck)) {
                        $result->addAttribute(Attribute::TYPE_TAG, $editable->getName(), $content, false, $targetContent);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param Document $document
     * @param AttributeSet $result
     *
     * @return DocumentDataExtractor
     */
    protected function addSettings(Document $document, AttributeSet $result): DocumentDataExtractor
    {
        $service = new Document\Service;
        $translations = $service->getTranslations($document);

        if ($document instanceof Document\Page) {
            $data = [
                'title' => $document->getTitle(),
                'description' => $document->getDescription(),
            ];

            $targetData = [];
            foreach ($result->getTargetLanguages() as $targetLanguage) {
                if (isset($translations[$targetLanguage])) {
                    $targetDocument = Document::getById($translations[$targetLanguage]);

                    if ($targetDocument instanceof  Document\PageSnippet) {
                        $targetData['title'][$targetLanguage] = $document->getTitle();
                        $targetData['description'][$targetLanguage] = $document->getDescription();
                    }
                }
            }

            foreach ($data as $key => $content) {
                if (!empty(trim($content))) {
                    $result->addAttribute(Attribute::TYPE_SETTINGS, $key, $content, false, $targetData[$key] ?? []);
                }
            }
        }

        return $this;
    }

    protected function doExportProperty(Property $property): bool
    {
        return parent::doExportProperty($property) && !in_array($property->getName(), [
                    'language',
                    'navigation_target',
                    'navigation_exclude',
                    'navigation_class',
                    'navigation_anchor',
                    'navigation_parameters',
                    'navigation_relation',
                    'navigation_accesskey',
                    'navigation_tabindex',
                ]);
    }
}
