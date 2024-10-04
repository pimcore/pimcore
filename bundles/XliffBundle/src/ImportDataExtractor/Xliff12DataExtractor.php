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

namespace Pimcore\Bundle\XliffBundle\ImportDataExtractor;

use Exception;
use Locale;
use Pimcore\Bundle\XliffBundle\AttributeSet\AttributeSet;
use Pimcore\Bundle\XliffBundle\Escaper\Xliff12Escaper;
use Pimcore\Bundle\XliffBundle\ExportService\Exporter\Xliff12Exporter;
use Pimcore\Bundle\XliffBundle\ImportDataExtractor\TranslationItemResolver\TranslationItemResolverInterface;
use Pimcore\Tool;
use SimpleXMLElement;

class Xliff12DataExtractor implements ImportDataExtractorInterface
{
    protected Xliff12Escaper $xliffEscaper;

    protected TranslationItemResolverInterface $translationItemResolver;

    public function __construct(Xliff12Escaper $xliffEscaper, TranslationItemResolverInterface $translationItemResolver)
    {
        $this->xliffEscaper = $xliffEscaper;
        $this->translationItemResolver = $translationItemResolver;
    }

    public function extractElement(string $importId, int $stepId): ?AttributeSet
    {
        $xliff = $this->loadFile($importId);

        $file = $xliff->file[$stepId];

        $target = $file['target-language'];

        // see https://en.wikipedia.org/wiki/IETF_language_tag
        $target = str_replace('-', '_', (string)$target);
        if (!Tool::isValidLanguage($target)) {
            $target = Locale::getPrimaryLanguage($target);
        }
        if (!Tool::isValidLanguage($target)) {
            throw new Exception(sprintf('invalid language %s', $file['target-language']));
        }

        [$type, $id] = explode('-', (string)$file['original']);

        $translationItem = $this->translationItemResolver->resolve($type, $id);

        if (empty($translationItem)) {
            return null;
        }

        $attributeSet = new AttributeSet($translationItem);
        $attributeSet->setTargetLanguages([$target]);
        if (!empty($file['source-language'])) {
            $attributeSet->setSourceLanguage((string)$file['source-language']);
        }

        foreach ($file->body->{'trans-unit'} as $transUnit) {
            [$type, $name] = explode(Xliff12Exporter::DELIMITER, (string)$transUnit['id']);

            if (!isset($transUnit->target)) {
                continue;
            }

            $content = $transUnit->target->asXml();
            $content = $this->xliffEscaper->unescapeXliff($content);

            $attributeSet->addAttribute($type, $name, $content);
        }

        return $attributeSet;
    }

    public function getImportFilePath(string $importId): string
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $importId . '.xliff';
    }

    public function countSteps(string $importId): int
    {
        $xliff = $this->loadFile($importId);

        return count($xliff->file);
    }

    /**
     * @throws Exception
     */
    private function loadFile(string $importId): SimpleXMLElement
    {
        return simplexml_load_file($this->getImportFilePath($importId), null, LIBXML_NOCDATA);
    }
}
