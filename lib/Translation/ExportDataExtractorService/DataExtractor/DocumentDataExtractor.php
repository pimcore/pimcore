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

namespace Pimcore\Translation\ExportDataExtractorService\DataExtractor;

use Pimcore\Document\Tag\TagUsageResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Property;
use Pimcore\Translation\AttributeSet\Attribute;
use Pimcore\Translation\AttributeSet\AttributeSet;
use Pimcore\Translation\TranslationItemCollection\TranslationItem;

class DocumentDataExtractor extends AbstractElementDataExtractor
{
    const EXPORTABLE_TAGS = ['wysiwyg', 'input', 'textarea', 'image', 'link'];

    /**
     * @var TagUsageResolver
     */
    private $tagUsageResolver;

    public function __construct(TagUsageResolver $tagUsageResolver)
    {
        $this->tagUsageResolver = $tagUsageResolver;
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
            ->addDoumentTags($document, $result)
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
    protected function addDoumentTags(Document $document, AttributeSet $result): DocumentDataExtractor
    {
        $editables = [];
        $service = new Document\Service;

        $translations = $service->getTranslations($document);

        if ($document instanceof Document\PageSnippet) {
            $tagNames = $this->tagUsageResolver->getUsedTagnames($document);
            foreach ($tagNames as $tagName) {
                if ($tag = $document->getEditable($tagName)) {
                    $editables[] = $tag;
                }
            }
        }

        foreach ($editables as $tag) {
            if (in_array($tag->getType(), self::EXPORTABLE_TAGS)) {
                if ($tag instanceof Document\Tag\Image || $tag instanceof Document\Tag\Link) {
                    $content = $tag->getText();
                } else {
                    $content = $tag->getData();
                }

                $targetContent = [];
                foreach ($result->getTargetLanguages() as $targetLanguage) {
                    if (isset($translations[$targetLanguage])) {
                        $targetDocument = Document::getById($translations[$targetLanguage]);

                        if ($targetDocument instanceof  Document\PageSnippet) {
                            $targetTag = $targetDocument->getEditable($tag->getName());
                            if ($targetTag instanceof Document\Tag\Image || $targetTag instanceof Document\Tag\Link) {
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
                        $result->addAttribute(Attribute::TYPE_TAG, $tag->getName(), $content, false, $targetContent);
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
