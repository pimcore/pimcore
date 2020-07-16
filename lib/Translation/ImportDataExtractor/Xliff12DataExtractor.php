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

namespace Pimcore\Translation\ImportDataExtractor;

use Pimcore\Tool;
use Pimcore\Translation\AttributeSet\AttributeSet;
use Pimcore\Translation\Escaper\Xliff12Escaper;
use Pimcore\Translation\ExportService\Exporter\Xliff12Exporter;
use Pimcore\Translation\ImportDataExtractor\TranslationItemResolver\TranslationItemResolverInterface;

class Xliff12DataExtractor implements ImportDataExtractorInterface
{
    /**
     * @var Xliff12Escaper
     */
    protected $xliffEscaper;

    /**
     * @var TranslationItemResolverInterface
     */
    protected $translationItemResolver;

    public function __construct(Xliff12Escaper $xliffEscaper, TranslationItemResolverInterface $translationItemResolver)
    {
        $this->xliffEscaper = $xliffEscaper;
        $this->translationItemResolver = $translationItemResolver;
    }

    /**
     * @inheritdoc
     */
    public function extractElement(string $importId, int $stepId): ?AttributeSet
    {
        $xliff = $this->loadFile($importId);

        $file = $xliff->file[$stepId];

        $target = $file['target-language'];

        // see https://en.wikipedia.org/wiki/IETF_language_tag
        $target = str_replace('-', '_', $target);
        if (!Tool::isValidLanguage($target)) {
            $target = \Locale::getPrimaryLanguage($target);
        }
        if (!Tool::isValidLanguage($target)) {
            throw new \Exception(sprintf('invalid language %s', $file['target-language']));
        }

        list($type, $id) = explode('-', $file['original']);

        $translationItem = $this->translationItemResolver->resolve($type, $id);

        if (empty($translationItem)) {
            return null;
        }

        $attributeSet = new AttributeSet($translationItem);
        $attributeSet->setTargetLanguages([$target]);
        if (!empty($file['source-language'])) {
            $attributeSet->setSourceLanguage($file['source-language']);
        }

        foreach ($file->body->{'trans-unit'} as $transUnit) {
            list($type, $name) = explode(Xliff12Exporter::DELIMITER, $transUnit['id']);

            if (!isset($transUnit->target)) {
                continue;
            }

            $content = $transUnit->target->asXml();
            $content = $this->xliffEscaper->unescapeXliff($content);

            $attributeSet->addAttribute($type, $name, $content);
        }

        return $attributeSet;
    }

    /**
     * @inheritdoc
     */
    public function getImportFilePath(string $importId): string
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $importId . '.xliff';
    }

    /**
     * @inheritdoc
     */
    public function countSteps(string $importId): int
    {
        $xliff = $this->loadFile($importId);

        return count($xliff->file);
    }

    /**
     * @param string $importId
     *
     * @return \SimpleXMLElement
     *
     * @throws \Exception
     */
    private function loadFile(string $importId): \SimpleXMLElement
    {
        return simplexml_load_file($this->getImportFilePath($importId), null, LIBXML_NOCDATA);
    }
}
