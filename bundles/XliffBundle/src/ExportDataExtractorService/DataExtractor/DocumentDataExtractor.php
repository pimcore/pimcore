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

namespace Pimcore\Bundle\XliffBundle\ExportDataExtractorService\DataExtractor;

use Exception;
use Pimcore\Bundle\XliffBundle\AttributeSet\Attribute;
use Pimcore\Bundle\XliffBundle\AttributeSet\AttributeSet;
use Pimcore\Bundle\XliffBundle\TranslationItemCollection\TranslationItem;
use Pimcore\Document\Editable\EditableUsageResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Property;

class DocumentDataExtractor extends AbstractElementDataExtractor
{
    const EXPORTABLE_TAGS = ['wysiwyg', 'input', 'textarea', 'image', 'link'];

    private EditableUsageResolver $EditableUsageResolver;

    public function __construct(EditableUsageResolver $EditableUsageResolver)
    {
        $this->EditableUsageResolver = $EditableUsageResolver;
    }

    /**
     * @param string[] $targetLanguages
     *
     * @throws Exception
     */
    public function extract(TranslationItem $translationItem, string $sourceLanguage, array $targetLanguages): AttributeSet
    {
        $document = $translationItem->getElement();

        $result = parent::extract($translationItem, $sourceLanguage, $targetLanguages);

        if (!$document instanceof Document) {
            throw new Exception('only documents allowed');
        }

        $this
            ->addDocumentEditables($document, $result)
            ->addSettings($document, $result);

        return $result;
    }

    /**
     * @deprecated
     */
    protected function addDoumentEditables(Document $document, AttributeSet $result): DocumentDataExtractor
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.1',
            'Using "%s" is deprecated and will be removed in Pimcore 12, use "%s" instead.',
            'addDoumentEditables',
            'addDocumentEditables'
        );

        return $this->addDocumentEditables($document, $result);
    }

    protected function addDocumentEditables(Document $document, AttributeSet $result): DocumentDataExtractor
    {
        $editables = [];
        $service = new Document\Service;

        $translations = $service->getTranslations($document);

        $this->resetSourceDocument($document, $result, $translations);

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

    protected function addSettings(Document $document, AttributeSet $result): DocumentDataExtractor
    {
        $service = new Document\Service;
        $translations = $service->getTranslations($document);

        $this->resetSourceDocument($document, $result, $translations);

        if ($document instanceof Document\Page) {
            $data = [
                'title' => $document->getTitle(),
                'description' => $document->getDescription(),
            ];

            $targetData = [];
            foreach ($result->getTargetLanguages() as $targetLanguage) {
                if (isset($translations[$targetLanguage])) {
                    $targetDocument = Document::getById($translations[$targetLanguage]);

                    if ($targetDocument instanceof  Document\Page) {
                        $targetData['title'][$targetLanguage] = $targetDocument->getTitle();
                        $targetData['description'][$targetLanguage] = $targetDocument->getDescription();
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

    private function resetSourceDocument(Document &$document, AttributeSet $result, array $translations): void
    {
        if ($result->getSourceLanguage() != $result->getTargetLanguages()) {
            $sourceDocumentId = $translations[$result->getSourceLanguage()] ?? false;
            if ($sourceDocumentId) {
                $sourceDocument = Document::getById($sourceDocumentId);

                if ($sourceDocument instanceof Document\PageSnippet) {
                    $document = $sourceDocument;
                }
            }
        }
    }
}
