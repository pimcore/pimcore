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

namespace Pimcore\Web2Print\Processor;

use Pimcore\Config;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Web2Print\Processor;

class PdfReactor8 extends Processor
{
    /**
     * returns the default web2print config
     *
     * @param $config
     *
     * @return array
     */
    protected function getConfig($config)
    {
        $config = (object)$config;
        $web2PrintConfig = Config::getWeb2PrintConfig();
        $reactorConfig = [
            'document' => '',
            'baseURL' => (string)$web2PrintConfig->pdfreactorBaseUrl,
            'author' => $config->author ? $config->author : '',
            'title' => $config->title ? $config->title : '',
            'addLinks' => $config->links == 'true',
            'addBookmarks' => $config->bookmarks == 'true',
            'javaScriptMode' => $config->javaScriptMode,
            'defaultColorSpace' => $config->colorspace,
            'encryption' => $config->encryption,
            'addTags' => $config->tags == 'true',
            'logLevel' => $config->loglevel
        ];
        if ($config->viewerPreference) {
            $reactorConfig['viewerPreferences'] = [$config->viewerPreference];
        }
        if (trim($web2PrintConfig->pdfreactorLicence)) {
            $reactorConfig['licenseKey'] = trim($web2PrintConfig->pdfreactorLicence);
        }

        return $reactorConfig;
    }

    /**
     * @return \PDFreactor
     */
    protected function getClient()
    {
        $web2PrintConfig = Config::getWeb2PrintConfig();

        include_once(__DIR__ . '/api/v' . $web2PrintConfig->get('pdfreactorVersion', '8.0') . '/PDFreactor.class.php');

        $port = ((string)$web2PrintConfig->pdfreactorServerPort) ? (string)$web2PrintConfig->pdfreactorServerPort : '9423';
        $protocol = ((string)$web2PrintConfig->pdfreactorProtocol) ? (string)$web2PrintConfig->pdfreactorProtocol : 'http';

        return new \PDFreactor($protocol . '://' . $web2PrintConfig->pdfreactorServer . ':' . $port . '/service/rest');
    }

    /**
     * returns the path to the generated pdf file
     *
     * @param string $html
     * @param array $params
     * @param bool $returnFilePath return the path to the pdf file or the content
     *
     * @return string
     */
    public function getPdfFromString($html, $params = [], $returnFilePath = false)
    {
        $pdfreactor = $this->getClient();

        $customConfig = (array)$params['adapterConfig'];
        $reactorConfig = $this->getConfig($customConfig);

        if (!array_keys($customConfig, 'addLinks')) {
            $customConfig['addLinks'] = true;
        }

        $reactorConfig = array_merge($reactorConfig, $customConfig); //add additional configs

        $reactorConfig['document'] = $this->processHtml($html, $params);
        $pdf = $pdfreactor->convert($reactorConfig);
        $pdf = base64_decode($pdf->document);
        if (!$returnFilePath) {
            return $pdf;
        } else {
            $dstFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . uniqid('web2print_') . '.pdf';
            file_put_contents($dstFile, $pdf);

            return $dstFile;
        }
    }

    protected function buildPdf(Document\PrintAbstract $document, $config)
    {
        $params = [];
        $params['printermarks'] = $config->printermarks == 'true';
        $params['screenResolutionImages'] = $config->screenResolutionImages == 'true';
        $params['colorspace'] = $config->colorspace;

        $this->updateStatus($document->getId(), 10, 'start_html_rendering');
        $html = $document->renderDocument($params);

        $this->updateStatus($document->getId(), 40, 'finished_html_rendering');

        $filePath = PIMCORE_TEMPORARY_DIRECTORY . '/pdf-reactor-input-' . $document->getId() . '.html';

        file_put_contents($filePath, $html);
        $html = null;

        $this->updateStatus($document->getId(), 45, 'saved_html_file');

        ini_set('default_socket_timeout', 3000);
        ini_set('max_input_time', -1);

        $pdfreactor = $this->getClient();
        $filePath = str_replace(PIMCORE_WEB_ROOT, '', $filePath);

        $reactorConfig = $this->getConfig($config);
        $web2PrintConfig = Config::getWeb2PrintConfig();
        $reactorConfig['document'] = (string)$web2PrintConfig->pdfreactorBaseUrl . $filePath;

        try {
            $progress = new \stdClass();
            $progress->finished = false;

            $processId = $pdfreactor->convertAsync($reactorConfig);

            while (!$progress->finished) {
                $progress = $pdfreactor->getProgress($processId);
                $this->updateStatus($document->getId(), 50 + ($progress->progress / 2), 'pdf_conversion');

                Logger::info('PDF converting progress: ' . $progress->progress . '%');
                sleep(2);
            }

            $this->updateStatus($document->getId(), 100, 'saving_pdf_document');
            $result = $pdfreactor->getDocument($processId);
            $pdf = base64_decode($result->document);
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
        include_once(__DIR__ . '/api/v' . Config::getWeb2PrintConfig()->get('pdfreactorVersion', '8.0') . '/PDFreactor.class.php');

        $options = [];

        $options[] = ['name' => 'author', 'type' => 'text', 'default' => ''];
        $options[] = ['name' => 'title', 'type' => 'text', 'default' => ''];
        $options[] = ['name' => 'printermarks', 'type' => 'bool', 'default' => ''];
        $options[] = ['name' => 'links', 'type' => 'bool', 'default' => true];
        $options[] = ['name' => 'bookmarks', 'type' => 'bool', 'default' => true];
        $options[] = ['name' => 'tags', 'type' => 'bool', 'default' => true];
        $options[] = [
            'name' => 'javaScriptMode',
            'type' => 'select',
            'values' => [\JavaScriptMode::ENABLED, \JavaScriptMode::DISABLED, \JavaScriptMode::ENABLED_NO_LAYOUT],
            'default' => \JavaScriptMode::ENABLED
        ];

        $options[] = [
            'name' => 'viewerPreference',
            'type' => 'select',
            'values' => [\ViewerPreferences::PAGE_LAYOUT_SINGLE_PAGE, \ViewerPreferences::PAGE_LAYOUT_TWO_COLUMN_LEFT, \ViewerPreferences::PAGE_LAYOUT_TWO_COLUMN_RIGHT],
            'default' => \ViewerPreferences::PAGE_LAYOUT_SINGLE_PAGE
        ];

        $options[] = [
            'name' => 'colorspace',
            'type' => 'select',
            'values' => [\ColorSpace::CMYK, \ColorSpace::RGB],
            'default' => \ColorSpace::CMYK
        ];

        $options[] = [
            'name' => 'encryption',
            'type' => 'select',
            'values' => [\Encryption::NONE, \Encryption::TYPE_40, \Encryption::TYPE_128],
            'default' => \Encryption::NONE
        ];

        $options[] = [
            'name' => 'loglevel',
            'type' => 'select',
            'values' => [\LogLevel::FATAL, \LogLevel::WARN, \LogLevel::INFO, \LogLevel::DEBUG, \LogLevel::PERFORMANCE],
            'default' => \LogLevel::FATAL
        ];

        return $options;
    }
}
