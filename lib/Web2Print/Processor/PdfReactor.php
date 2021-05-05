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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Web2Print\Processor;

use com\realobjects\pdfreactor\webservice\client\ColorSpace;
use com\realobjects\pdfreactor\webservice\client\Encryption;
use com\realobjects\pdfreactor\webservice\client\HttpsMode;
use com\realobjects\pdfreactor\webservice\client\JavaScriptMode;
use com\realobjects\pdfreactor\webservice\client\LogLevel;
use com\realobjects\pdfreactor\webservice\client\ViewerPreferences;
use Pimcore\Config;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\PrintConfigEvent;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Web2Print\Processor;

class PdfReactor extends Processor
{
    /**
     * returns the default web2print config
     *
     * @param object $config
     *
     * @return array
     */
    protected function getConfig($config)
    {
        $config = (object)$config;
        $web2PrintConfig = Config::getWeb2PrintConfig();
        $reactorConfig = [
            'document' => '',
            'baseURL' => (string)$web2PrintConfig->get('pdfreactorBaseUrl'),
            'author' => $config->author ?? '',
            'title' => $config->title ?? '',
            'addLinks' => isset($config->links) && $config->links === true,
            'addBookmarks' => isset($config->bookmarks) && $config->bookmarks === true,
            'javaScriptMode' => $config->javaScriptMode ?? JavaScriptMode::ENABLED,
            'defaultColorSpace' => $config->colorspace ?? ColorSpace::CMYK,
            'encryption' => $config->encryption ?? Encryption::NONE,
            'addTags' => isset($config->tags) && $config->tags === true,
            'logLevel' => $config->loglevel ?? LogLevel::FATAL,
            'enableDebugMode' => $web2PrintConfig->get('pdfreactorEnableDebugMode') || (isset($config->enableDebugMode) && $config->enableDebugMode === true),
            'addOverprint' => isset($config->addOverprint) && $config->addOverprint === true,
            'httpsMode' => $web2PrintConfig->get('pdfreactorEnableLenientHttpsMode') ? HttpsMode::LENIENT : HttpsMode::STRICT,
        ];
        if (!empty($config->viewerPreference)) {
            $reactorConfig['viewerPreferences'] = [$config->viewerPreference];
        }
        if (trim($web2PrintConfig->get('pdfreactorLicence'))) {
            $reactorConfig['licenseKey'] = trim($web2PrintConfig->get('pdfreactorLicence'));
        }

        return $reactorConfig;
    }

    /**
     * @return \com\realobjects\pdfreactor\webservice\client\PDFreactor
     */
    protected function getClient()
    {
        $web2PrintConfig = Config::getWeb2PrintConfig();
        $this->includeApi();

        $port = ((string)$web2PrintConfig->get('pdfreactorServerPort')) ? (string)$web2PrintConfig->get('pdfreactorServerPort') : '9423';
        $protocol = ((string)$web2PrintConfig->get('pdfreactorProtocol')) ? (string)$web2PrintConfig->get('pdfreactorProtocol') : 'http';

        $pdfreactor = new \com\realobjects\pdfreactor\webservice\client\PDFreactor($protocol . '://' . $web2PrintConfig->get('pdfreactorServer') . ':' . $port . '/service/rest');

        if (trim($web2PrintConfig->get('pdfreactorApiKey'))) {
            $pdfreactor->apiKey = trim($web2PrintConfig->get('pdfreactorApiKey'));
        }

        return $pdfreactor;
    }

    /**
     * {@internal}
     */
    public function getPdfFromString($html, $params = [], $returnFilePath = false)
    {
        $pdfreactor = $this->getClient();

        $customConfig = (array)$params['adapterConfig'];
        $reactorConfig = $this->getConfig((object)$customConfig);

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

    /**
     * {@internal}
     */
    protected function buildPdf(Document\PrintAbstract $document, $config)
    {
        $this->includeApi();

        $params = [];
        $params['printermarks'] = isset($config->printermarks) && $config->printermarks === true;
        $params['screenResolutionImages'] = isset($config->screenResolutionImages) && $config->screenResolutionImages === true;
        $params['colorspace'] = $config->colorspace ?? ColorSpace::CMYK;

        $this->updateStatus($document->getId(), 10, 'start_html_rendering');
        $html = $document->renderDocument($params);
        $this->updateStatus($document->getId(), 40, 'finished_html_rendering');

        ini_set('default_socket_timeout', 3000);
        ini_set('max_input_time', -1);

        $pdfreactor = $this->getClient();

        $reactorConfig = $this->getConfig($config);
        $reactorConfig['document'] = $this->processHtml($html, $params);

        $event = new PrintConfigEvent($this, ['config' => $config, 'reactorConfig' => $reactorConfig, 'document' => $document]);
        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::PRINT_MODIFY_PROCESSING_CONFIG);

        $reactorConfig = $event->getArguments()['reactorConfig'];

        $progress = new \stdClass();
        $progress->finished = false;

        $connectionSettings = [];
        $processId = $pdfreactor->convertAsync($reactorConfig, $connectionSettings);

        while (!$progress->finished) {
            $progress = $pdfreactor->getProgress($processId, $connectionSettings);
            $this->updateStatus($document->getId(), 50 + ($progress->progress / 2), 'pdf_conversion');

            Logger::info('PDF converting progress: ' . $progress->progress . '%');
            sleep(2);
        }

        $this->updateStatus($document->getId(), 100, 'saving_pdf_document');
        $result = $pdfreactor->getDocument($processId, $connectionSettings);

        return base64_decode($result->document);
    }

    /**
     * {@internal}
     */
    public function getProcessingOptions()
    {
        $this->includeApi();

        $options = [];

        $options[] = ['name' => 'author', 'type' => 'text', 'default' => ''];
        $options[] = ['name' => 'title', 'type' => 'text', 'default' => ''];
        $options[] = ['name' => 'printermarks', 'type' => 'bool', 'default' => false];
        $options[] = ['name' => 'addOverprint', 'type' => 'bool', 'default' => false];
        $options[] = ['name' => 'links', 'type' => 'bool', 'default' => true];
        $options[] = ['name' => 'bookmarks', 'type' => 'bool', 'default' => true];
        $options[] = ['name' => 'tags', 'type' => 'bool', 'default' => true];
        $options[] = [
            'name' => 'javaScriptMode',
            'type' => 'select',
            'values' => [JavaScriptMode::ENABLED, JavaScriptMode::DISABLED, JavaScriptMode::ENABLED_NO_LAYOUT],
            'default' => JavaScriptMode::ENABLED,
        ];

        $options[] = [
            'name' => 'viewerPreference',
            'type' => 'select',
            'values' => [ViewerPreferences::PAGE_LAYOUT_SINGLE_PAGE, ViewerPreferences::PAGE_LAYOUT_TWO_COLUMN_LEFT, ViewerPreferences::PAGE_LAYOUT_TWO_COLUMN_RIGHT],
            'default' => ViewerPreferences::PAGE_LAYOUT_SINGLE_PAGE,
        ];

        $options[] = [
            'name' => 'colorspace',
            'type' => 'select',
            'values' => [ColorSpace::CMYK, ColorSpace::RGB],
            'default' => ColorSpace::CMYK,
        ];

        $options[] = [
            'name' => 'encryption',
            'type' => 'select',
            'values' => [Encryption::NONE, Encryption::TYPE_40, Encryption::TYPE_128],
            'default' => Encryption::NONE,
        ];

        $options[] = [
            'name' => 'loglevel',
            'type' => 'select',
            'values' => [LogLevel::FATAL, LogLevel::WARN, LogLevel::INFO, LogLevel::DEBUG, LogLevel::PERFORMANCE],
            'default' => LogLevel::FATAL,
        ];

        $options[] = ['name' => 'enableDebugMode', 'type' => 'bool', 'default' => false];

        $event = new PrintConfigEvent($this, [
            'options' => $options,
        ]);

        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::PRINT_MODIFY_PROCESSING_OPTIONS);

        return (array)$event->getArguments()['options'];
    }

    protected function includeApi()
    {
        include_once(__DIR__ . '/api/PDFreactor.class.php');
    }
}
