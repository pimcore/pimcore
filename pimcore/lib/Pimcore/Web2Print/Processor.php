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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Web2Print;

use Pimcore\Config;
use \Pimcore\Tool;
use \Pimcore\Model;
use \Pimcore\Model\Document;
use Pimcore\Web2Print\Processor\PdfReactor8;
use Pimcore\Web2Print\Processor\WkHtmlToPdf;

abstract class Processor
{
    public $documentId = -1;
    public $config = [];
    public $processId = "";

    /**
     * @return PdfReactor8|WkHtmlToPdf
     * @throws \Exception
     */
    public static function getInstance()
    {
        $config = Config::getWeb2PrintConfig();

        if ($config->generalTool == "pdfreactor") {
            return new PdfReactor8();
        } elseif ($config->generalTool == "wkhtmltopdf") {
            return new WkHtmlToPdf();
        } else {
            throw new \Exception("Invalid Configuation");
        }
    }

    /**
     * @param $documentId
     * @param $config
     * @throws \Exception
     */
    public function preparePdfGeneration($documentId, $config)
    {
        $this->documentId = $documentId;
        $this->config = $config;
        $this->processId = uniqid();
        $this->saveJobConfigObjectFile();

        $cmd = Tool\Console::getPhpCli() . " " . realpath(PIMCORE_PATH . DIRECTORY_SEPARATOR . "cli" . DIRECTORY_SEPARATOR . "console.php"). " web2print:pdf-creation -p " . $this->processId;

        \Logger::info($cmd);

        if (!$config['disableBackgroundExecution']) {
            Tool\Console::execInBackground($cmd, PIMCORE_LOG_DIRECTORY . DIRECTORY_SEPARATOR . "web2print-output.log");
        } else {
            Processor::getInstance()->startPdfGeneration($this->processId);
        }
    }

    /**
     * @param $processId
     * @throws \Exception
     */
    public function startPdfGeneration($processId)
    {
        $this->loadJobConfigObject($processId);

        $document = $this->getPrintDocument();

        // check if there is already a generating process running, wait if so ...
        Model\Tool\Lock::acquire($document->getLockKey(), 0);

        try {
            $pdf = $this->buildPdf($document, $this->config);
            file_put_contents($document->getPdfFileName(), $pdf);
            $creationDate = \Zend_Date::now();
            $document->setLastGenerated(($creationDate->get() + 1));
            $document->save();
        } catch (\Exception $e) {
            $document->save();
            \Logger::err($e);
        }

        Model\Tool\Lock::release($document->getLockKey());

        @unlink($this->getJobConfigFile($processId));
    }

    /**
     * @param Document\PrintAbstract $document
     * @param $config
     * @return mixed
     */
    abstract protected function buildPdf(Document\PrintAbstract $document, $config);


    /**
     * @return bool
     */
    protected function saveJobConfigObjectFile()
    {
        file_put_contents($this->getJobConfigFile($this->processId), json_encode($this->getJobConfigObject()));
        return true;
    }

    /**
     * @return \stdClass
     */
    protected function getJobConfigObject()
    {
        $config = new \stdClass();
        $config->documentId = $this->documentId;
        $config->config = $this->config;
        $config->processId = $this->processId;

        return $config;
    }

    /**
     * @param $config
     */
    protected function loadJobConfigObject($processId)
    {
        $config = json_decode(file_get_contents($this->getJobConfigFile($processId)));
        $this->documentId = $config->documentId;
        $this->config = $config->config;
        $this->processId = $config->processId;
    }

    /**
     * @return Document\PrintAbstract
     * @throws \Exception
     */
    protected function getPrintDocument()
    {
        $document = Document\Printpage::getById($this->documentId);
        if (empty($document)) {
            throw new \Exception("PrintDocument with " . $this->documentId . " not found.");
        }
        return $document;
    }

    /**
     * @param $processId
     * @return string
     */
    public static function getJobConfigFile($processId)
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . "pdf-creation-job-" . $processId . ".json";
    }

    /**
     * @return array
     */
    abstract public function getProcessingOptions();
}
