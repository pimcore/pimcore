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

namespace Pimcore\Translation\ExportService\Exporter;

use Pimcore\File;
use Pimcore\Translation\AttributeSet\AttributeSet;
use Pimcore\Translation\Escaper\Xliff12Escaper;

class Xliff12Exporter implements ExporterInterface
{
    const DELIMITER = '~-~';

    /**
     * @var Xliff12Escaper
     */
    private $xliffEscaper;

    public function __construct(Xliff12Escaper $xliffEscaper)
    {
        $this->xliffEscaper = $xliffEscaper;
    }

    /**
     * @inheritdoc
     */
    public function export(AttributeSet $attributeSet, string $exportId = null): string
    {
        $exportId = $exportId ?: uniqid();

        $exportFile = $this->getExportFilePath($exportId);

        if ($attributeSet->isEmpty()) {
            return $exportFile;
        }

        $xliff = simplexml_load_file($exportFile, null, LIBXML_NOCDATA);

        foreach ($attributeSet->getTargetLanguages() as $targetLanguage) {
            $file = $xliff->addChild('file');
            $file->addAttribute('original', $attributeSet->getTranslationItem()->getType() . '-' . $attributeSet->getTranslationItem()->getId());
            $file->addAttribute('source-language', $attributeSet->getSourceLanguage());
            $file->addAttribute('target-language', $targetLanguage);
            $file->addAttribute('datatype', 'html');
            $file->addAttribute('tool', 'pimcore');
            $file->addAttribute('category', $attributeSet->getTranslationItem()->getType());

            $file->addChild('header');

            $body = $file->addChild('body');

            foreach ($attributeSet->getAttributes() as $attribute) {
                if ($attribute->isReadonly()) {
                    continue;
                }

                $this->addTransUnitNode($body, $attribute->getType() . self::DELIMITER . $attribute->getName(), $attribute->getContent(), $attributeSet->getSourceLanguage());
            }
        }

        $xliff->asXML($exportFile);

        return $exportFile;
    }

    /**
     * @inheritdoc
     */
    public function getExportFilePath(string $exportId): string
    {
        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $exportId . '.xliff';
        if (!is_file($exportFile)) {
            // create initial xml file structure
            File::put($exportFile, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<xliff version="1.2"></xliff>');
        }

        return $exportFile;
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string
    {
        return 'application/x-xliff+xml';
    }

    /**
     * @param $xml
     * @param $name
     * @param $content
     * @param $source
     */
    protected function addTransUnitNode(\SimpleXMLElement $xml, $name, $content, $source)
    {
        $transUnit = $xml->addChild('trans-unit');
        $transUnit->addAttribute('id', htmlentities($name));

        $sourceNode = $transUnit->addChild('source');
        $sourceNode->addAttribute('xmlns:xml:lang', $source);

        $node = dom_import_simplexml($sourceNode);
        $no = $node->ownerDocument;
        $f = $no->createDocumentFragment();
        $f->appendXML($this->xliffEscaper->escapeXliff($content));
        @$node->appendChild($f);
    }
}
