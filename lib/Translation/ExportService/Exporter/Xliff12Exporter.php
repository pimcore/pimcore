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

    /**
     * @var \SimpleXMLElement|null
     */
    private $xliffFile;

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

        $this->prepareExportFile($exportFile);

        foreach ($attributeSet->getTargetLanguages() as $targetLanguage) {
            $file = $this->xliffFile->addChild('file');
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

                $targetContent = $attribute->getTargetContent()[$targetLanguage] ?? null;

                $this->addTransUnitNode($body, $attribute->getType() . self::DELIMITER . $attribute->getName(), $attribute->getContent(), $attributeSet->getSourceLanguage(), $targetContent, $targetLanguage);
            }
        }

        $this->xliffFile->asXML($exportFile);

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
     * @param string $exportFilePath
     *
     */
    protected function prepareExportFile(string $exportFilePath)
    {
        if ($this->xliffFile === null) {
            $dom = new \DOMDocument();
            $dom->loadXML(file_get_contents($exportFilePath));
            $this->xliffFile = simplexml_import_dom($dom);
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string
    {
        return 'application/x-xliff+xml';
    }

    /**
     * @param \SimpleXMLElement $xml
     * @param string $name
     * @param string $sourceContent
     * @param string $sourceLang
     * @param string $targetContent
     * @param string $targetLang
     */
    protected function addTransUnitNode(\SimpleXMLElement $xml, $name, $sourceContent, $sourceLang, $targetContent, $targetLang)
    {
        $transUnit = $xml->addChild('trans-unit');
        $transUnit->addAttribute('id', htmlentities($name));

        $sourceNode = $transUnit->addChild('source');
        $sourceNode->addAttribute('xmlns:xml:lang', $sourceLang);

        $node = dom_import_simplexml($sourceNode);
        $no = $node->ownerDocument;
        $f = $no->createDocumentFragment();
        $f->appendXML($this->xliffEscaper->escapeXliff($sourceContent));
        @$node->appendChild($f);

        if (!empty($targetContent)) {
            $targetNode = $transUnit->addChild('target');
            $targetNode->addAttribute('xmlns:xml:lang', $targetLang);

            $tNode = dom_import_simplexml($targetNode);
            $targetFragment = $no->createDocumentFragment();
            $targetFragment->appendXML($this->xliffEscaper->escapeXliff($targetContent));
            @$tNode->appendChild($targetFragment);
        }
    }
}
