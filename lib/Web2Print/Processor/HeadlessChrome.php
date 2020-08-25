<?php

namespace Pimcore\Web2Print\Processor;

use Pimcore\Config;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Tests\Helper\Pimcore;
use Pimcore\Tool;
use Pimcore\Web2Print\Processor;
use Spiritix\Html2Pdf\Converter;
use Spiritix\Html2Pdf\Input\StringInput;
use Spiritix\Html2Pdf\Input\UrlInput;
use Spiritix\Html2Pdf\Output\FileOutput;
use Spiritix\Html2Pdf\Output\StringOutput;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

class HeadlessChrome extends Processor
{
    const NODE_PATH = '/usr/local/node-v14.4.0-linux-x64/bin/node';

    protected function buildPdf(Document\PrintAbstract $document, $config)
    {
        $this->config = $config;
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
            throw new \Exception('Error during REST-Request:' . $e->getMessage());
        }

        $document->setLastGenerateMessage('');
        return $pdf;
    }

    public function getProcessingOptions()
    {
        return [];
    }

    public function getPdfFromString($html, $params = [], $returnFilePath = false)
    {
        $path = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . uniqid('web2print_') . '.pdf';
        $input = new StringInput();
        $input->setHtml($html);

        $output = $returnFilePath ? new FileOutput() : new StringOutput();
        $converter = new Converter($input, $output);
        $converter->setNodePath(self::NODE_PATH);
        $converter->setOptions($params);

        /** @var StringOutput $output */
        $output = $converter->convert();

        if($returnFilePath){
            $output->store($path);
            return $path;
        }
        return $output->get();
    }

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
            'displayHeaderFooter' => true,
        ];
    }
}
