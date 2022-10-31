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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Web2Print\Processor;

use Pimcore\Config;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\PrintConfigEvent;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Web2Print\Processor;
use Spiritix\Html2Pdf\Converter;
use Spiritix\Html2Pdf\Input\StringInput;
use Spiritix\Html2Pdf\Output\FileOutput;
use Spiritix\Html2Pdf\Output\StringOutput;

class HeadlessChrome extends Processor
{
    private $nodePath = '';

    /**
     * @internal
     */
    protected function buildPdf(Document\PrintAbstract $document, $config)
    {
        $web2printConfig = Config::getWeb2PrintConfig();
        $web2printConfig = $web2printConfig->get('headlessChromeSettings');
        $web2printConfig = json_decode($web2printConfig, true);

        $config = (array) $config;

        $params = ['document' => $document];
        $this->updateStatus($document->getId(), 10, 'start_html_rendering');
        $html = $document->renderDocument($params);

        $html = $this->processHtml($html, $params);
        $this->updateStatus($document->getId(), 40, 'finished_html_rendering');

        if ($config) {
            foreach (['header', 'footer'] as $item) {
                if (key_exists($item, $config) && $config[$item] &&
                    $content = file_get_contents($config[$item])) {
                    $config[$item . 'Template'] = $content;
                }
                unset($config[$item]);
            }
            foreach (['Top', 'Right', 'Bottom', 'Left'] as $item) {
                $config['margin'][strtolower($item)] = $config['margin' . $item];
                unset($config['margin' . $item]);
            }
        }

        //Adds any other values that are not covered by the getProcessingOptions
        $config = array_merge($config, array_diff_key($web2printConfig, $config));

        try {
            $this->updateStatus($document->getId(), 50, 'pdf_conversion');
            $pdf = $this->getPdfFromString($html, $config);
            $this->updateStatus($document->getId(), 100, 'saving_pdf_document');
        } catch (\Exception $e) {
            Logger::error((string) $e);
            $document->setLastGenerateMessage($e->getMessage());

            throw new \Exception('Error during PDF-Generation:' . $e->getMessage());
        }

        $document->setLastGenerateMessage('');

        return $pdf;
    }

    /**
     * @internal
     */
    public function getProcessingOptions()
    {
        $web2printConfig = Config::getWeb2PrintConfig();
        $web2printConfig = $web2printConfig->get('headlessChromeSettings');
        $web2printConfig = json_decode($web2printConfig, true);

        $options[] = ['name' => 'width', 'type' => 'text', 'default' => $web2printConfig['width'] ?? ''];
        $options[] = ['name' => 'height', 'type' => 'text', 'default' => $web2printConfig['height'] ?? ''];
        $options[] = ['name' => 'displayHeaderFooter', 'type' => 'bool', 'default' => $web2printConfig['displayHeaderFooter'] ?? false];
        $options[] = ['name' => 'printBackground', 'type' => 'bool', 'default' => $web2printConfig['printBackground'] ?? false];
        $options[] = ['name' => 'landscape', 'type' => 'bool', 'default' => $web2printConfig['landscape'] ?? true];
        $options[] = ['name' => 'preferCSSPageSize', 'type' => 'bool', 'default' => $web2printConfig['preferCSSPageSize'] ?? false];
        $options[] = ['name' => 'omitBackground', 'type' => 'bool', 'default' => $web2printConfig['omitBackground'] ?? false];
        $options[] = ['name' => 'marginTop', 'type' => 'text', 'default' => $web2printConfig['margin']['top'] ?? ''];
        $options[] = ['name' => 'marginRight', 'type' => 'text', 'default' => $web2printConfig['margin']['right'] ?? ''];
        $options[] = ['name' => 'marginBottom', 'type' => 'text', 'default' => $web2printConfig['margin']['bottom'] ?? ''];
        $options[] = ['name' => 'marginLeft', 'type' => 'text', 'default' => $web2printConfig['margin']['left'] ?? ''];

        $event = new PrintConfigEvent($this, [
            'options' => $options,
        ]);

        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::PRINT_MODIFY_PROCESSING_OPTIONS);

        return (array)$event->getArgument('options');
    }

    /**
     * @internal
     */
    public function getPdfFromString($html, $params = [], $returnFilePath = false)
    {
        $params = $params ?: $this->getDefaultOptions();
        $input = new StringInput();
        $input->setHtml($html);

        $output = $returnFilePath ? new FileOutput() : new StringOutput();
        $converter = new Converter($input, $output);
        if ($this->nodePath) {
            $converter->setNodePath($this->nodePath);
        }
        $converter->setOptions($params);

        $output = $converter->convert();

        if ($returnFilePath) {
            $path = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . uniqid('web2print_') . '.pdf';
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
    private function getDefaultOptions(): array
    {
        return [
            'landscape' => false,
            'printBackground' => false,
            'format' => 'A4',
            'margin' => [
                'top' => '16 mm',
                'bottom' => '30 mm',
                'right' => '8 mm',
                'left' => '8 mm',
            ],
            'displayHeaderFooter' => false,
        ];
    }

    /**
     * @param string $nodePath
     *
     * @return $this
     */
    public function setNodePath(string $nodePath): self
    {
        $this->nodePath = $nodePath;

        return $this;
    }
}
