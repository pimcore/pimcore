<?php

namespace Pimcore\Web2Print\Processor;

use Pimcore\Config;
use Pimcore\Event\Model\PrintConfigEvent;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Web2Print\Processor;
use Pimcore\Event\DocumentEvents;
use Spiritix\Html2Pdf\Converter;
use Spiritix\Html2Pdf\Input\StringInput;
use Spiritix\Html2Pdf\Input\UrlInput;
use Spiritix\Html2Pdf\Output\FileOutput;
use Spiritix\Html2Pdf\Output\StringOutput;

class HeadlessChrome extends Processor
{
    private $nodePath = "";

    /**
     * @param Document\PrintAbstract $document
     * @param object $config
     * @return string
     * @throws \Exception
     */
    protected function buildPdf(Document\PrintAbstract $document, $config)
    {
        $web2printConfig = Config::getWeb2PrintConfig();
        $web2printConfig = $web2printConfig["headlessChromeSettings"];
        $web2printConfig = json_decode($web2printConfig, true);

        $params = ['document' => $document];
        $this->updateStatus($document->getId(), 10, 'start_html_rendering');
        $html = $document->renderDocument($params);

        $html = $this->processHtml($html, $params);
        $this->updateStatus($document->getId(), 40, 'finished_html_rendering');

        if($web2printConfig){
            foreach (["header", "footer"] as $item){
                if(key_exists($item, $web2printConfig) && $web2printConfig[$item] &&
                    $content = file_get_contents($web2printConfig[$item])){
                    $web2printConfig[$item . "Template"] = $content;
                }
                unset($web2printConfig[$item]);
            }
        }

        try {
            $this->updateStatus($document->getId(), 50, 'pdf_conversion');
            $pdf = $this->getPdfFromString($html, $web2printConfig ?: $this->getDefaultOptions());
            $this->updateStatus($document->getId(), 100, 'saving_pdf_document');
        } catch (\Exception $e) {
            Logger::error($e);
            $document->setLastGenerateMessage($e->getMessage());
            throw new \Exception('Error during PDF-Generation:' . $e->getMessage());
        }

        $document->setLastGenerateMessage('');
        return $pdf;
    }

    /**
     * @return array
     */
    public function getProcessingOptions()
    {
        $event = new PrintConfigEvent($this, [
            'options' => [],
        ]);
        \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::PRINT_MODIFY_PROCESSING_OPTIONS, $event);
        return (array)$event->getArgument('options');
    }

    /**
     * @param string $html
     * @param array $params
     * @param bool $returnFilePath
     * @return string
     */
    public function getPdfFromString($html, $params = [], $returnFilePath = false)
    {
        $path = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . uniqid('web2print_') . '.pdf';
        $input = new StringInput();
        $input->setHtml($html);

        $output = $returnFilePath ? new FileOutput() : new StringOutput();
        $converter = new Converter($input, $output);
        if($this->nodePath){
            $converter->setNodePath($this->nodePath);
        }
        $converter->setOptions($params);

        $output = $converter->convert();

        if($returnFilePath) {
            /** @var FileOutput $output */
            $output->store($path);
            return $path;
        }
        /** @var StringOutput $output */
        return $output->get();
    }

    /**
     * @return array
     */
    private function getDefaultOptions() : array {
        return [
            'landscape' => false,
            'printBackground' => false,
            'format' => "A4",
            'margin' => [
                'top' => "16 mm",
                'bottom' => "30 mm",
                'right' => "8 mm",
                'left' => "8 mm",
            ],
            'displayHeaderFooter' => false,
        ];
    }

    /**
     * @param string $nodePath
     * @return $this
     */
    public function setNodePath(string $nodePath) : self{
        $this->nodePath = $nodePath;
        return $this;
    }
}
