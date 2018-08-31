<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 08/05/2018
 * Time: 10:22
 */

namespace Pimcore\Translation\ImportDataExtractor;


use Pimcore\Model\Element;
use Pimcore\Tool;
use Pimcore\Translation\AttributeSet\AttributeSet;
use Pimcore\Translation\ExportService\Exporter\Xliff12Exporter;
use Pimcore\Translation\Escaper\Xliff12Escaper;
use Pimcore\Translation\ImportDataExtractor\TranslationItemResolver\TranslationItemResolverInterface;

class Xliff12DataExtractor implements ImportDataExtractorInterface
{
    /**
     * @var Xliff12Escaper
     */
    protected $xliffEscaper;

    /**
     * @var TranslationItemResolver\
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
    public function extractElement(string $importId, int $stepId): AttributeSet
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

        if(empty($translationItem)) {
            throw new \Exception('Could not resolve element ' . $file['original']);
        }

        $attributeSet = new AttributeSet($translationItem);
        $attributeSet->setTargetLanguages([$target]);
        if(!empty($file['source-language'])) {
            $attributeSet->setSourceLanguage($file['source-language']);
        }

        foreach ($file->body->{'trans-unit'} as $transUnit) {
            list($type, $name) = explode(Xliff12Exporter::DELIMITER, $transUnit['id']);
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
     * @return \SimpleXMLElement
     * @throws \Exception
     */
    private function loadFile(string $importId): \SimpleXMLElement
    {
        return simplexml_load_file($this->getImportFilePath($importId), null, LIBXML_NOCDATA);
    }
}
