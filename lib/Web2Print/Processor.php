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

namespace Pimcore\Web2Print;

use Pimcore\Config;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Tool;
use Pimcore\Web2Print\Processor\PdfReactor8;
use Pimcore\Web2Print\Processor\WkHtmlToPdf;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\Lock\LockInterface;

abstract class Processor
{
    /**
     * @var LockInterface|null
     */
    private static $lock = null;

    /**
     * @return PdfReactor8|WkHtmlToPdf
     *
     * @throws \Exception
     */
    public static function getInstance()
    {
        $config = Config::getWeb2PrintConfig();

        if ($config->get('generalTool') === 'pdfreactor') {
            return new PdfReactor8();
        } elseif ($config->get('generalTool') === 'wkhtmltopdf') {
            return new WkHtmlToPdf();
        } else {
            throw new \Exception('Invalid Configuation - ' . $config->get('generalTool'));
        }
    }

    /**
     * @param int $documentId
     * @param array $config
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function preparePdfGeneration($documentId, $config)
    {
        $document = $this->getPrintDocument($documentId);
        if (Model\Tool\TmpStore::get($document->getLockKey())) {
            throw new \Exception('Process with given document alredy running.');
        }
        Model\Tool\TmpStore::add($document->getLockKey(), true);

        $jobConfig = new \stdClass();
        $jobConfig->documentId = $documentId;
        $jobConfig->config = $config;

        $this->saveJobConfigObjectFile($jobConfig);
        $this->updateStatus($documentId, 0, 'prepare_pdf_generation');

        $args = ['-p ' . $jobConfig->documentId];

        $env = \Pimcore\Config::getEnvironment();
        if ($env !== false) {
            $args[] = '--env=' . $env;
        }

        $cmd = Tool\Console::getPhpCli() . ' ' . realpath(PIMCORE_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console'). ' pimcore:web2print:pdf-creation ' . implode(' ', $args);

        Logger::info($cmd);
        $disableBackgroundExecution = $config['disableBackgroundExecution'] ?? false;

        if (!$disableBackgroundExecution) {
            Tool\Console::execInBackground($cmd, PIMCORE_LOG_DIRECTORY . DIRECTORY_SEPARATOR . 'web2print-output.log');

            return true;
        }

        return (bool)self::getInstance()->startPdfGeneration($jobConfig->documentId);
    }

    /**
     * @param int $documentId
     *
     * @return string|null
     */
    public function startPdfGeneration($documentId)
    {
        $jobConfigFile = $this->loadJobConfigObject($documentId);

        $document = $this->getPrintDocument($documentId);

        $lock = self::getLock($document);
        // check if there is already a generating process running, wait if so ...
        $lock->acquire(true);

        $pdf = null;

        try {
            \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::PRINT_PRE_PDF_GENERATION, new DocumentEvent($document, [
                'processor' => $this,
                'jobConfig' => $jobConfigFile->config,
            ]));

            $pdf = $this->buildPdf($document, $jobConfigFile->config);
            file_put_contents($document->getPdfFileName(), $pdf);

            \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::PRINT_POST_PDF_GENERATION, new DocumentEvent($document, [
                'filename' => $document->getPdfFileName(),
                'pdf' => $pdf,
            ]));

            $document->setLastGenerated((time() + 1));
            $document->setLastGenerateMessage('');
            $document->save();
        } catch (\Exception $e) {
            Logger::err($e);
            $document->setLastGenerateMessage($e->getMessage());
            $document->save();
        }

        $lock->release();
        Model\Tool\TmpStore::delete($document->getLockKey());

        @unlink($this->getJobConfigFile($documentId));

        return $pdf;
    }

    /**
     * @param Document\PrintAbstract $document
     * @param object $config
     *
     * @return string
     *
     * @throws \Exception
     */
    abstract protected function buildPdf(Document\PrintAbstract $document, $config);

    /**
     * @param \stdClass $jobConfig
     *
     * @return bool
     */
    protected function saveJobConfigObjectFile($jobConfig)
    {
        file_put_contents($this->getJobConfigFile($jobConfig->documentId), json_encode($jobConfig));

        return true;
    }

    /**
     * @param int $documentId
     *
     * @return \stdClass
     */
    protected function loadJobConfigObject($documentId)
    {
        $jobConfig = json_decode(file_get_contents($this->getJobConfigFile($documentId)));

        return $jobConfig;
    }

    /**
     * @param int $documentId
     *
     * @return Document\PrintAbstract
     *
     * @throws \Exception
     */
    protected function getPrintDocument($documentId)
    {
        $document = Document\PrintAbstract::getById($documentId);
        if (empty($document)) {
            throw new \Exception('PrintDocument with ' . $documentId . ' not found.');
        }

        return $document;
    }

    /**
     * @param int $processId
     *
     * @return string
     */
    public static function getJobConfigFile($processId)
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'pdf-creation-job-' . $processId . '.json';
    }

    /**
     * @return array
     */
    abstract public function getProcessingOptions();

    /**
     * @param int $documentId
     * @param int $status
     * @param string $statusUpdate
     */
    protected function updateStatus($documentId, $status, $statusUpdate)
    {
        $jobConfig = $this->loadJobConfigObject($documentId);
        $jobConfig->status = $status;
        $jobConfig->statusUpdate = $statusUpdate;
        $this->saveJobConfigObjectFile($jobConfig);
    }

    /**
     * @param int $documentId
     *
     * @return array
     */
    public function getStatusUpdate($documentId)
    {
        $jobConfig = $this->loadJobConfigObject($documentId);
        if ($jobConfig) {
            return [
                'status' => $jobConfig->status,
                'statusUpdate' => $jobConfig->statusUpdate,
            ];
        }
    }

    /**
     * @param int $documentId
     *
     * @throws \Exception
     */
    public function cancelGeneration($documentId)
    {
        $document = Document\PrintAbstract::getById($documentId);
        if (empty($document)) {
            throw new \Exception('Document with id ' . $documentId . ' not found.');
        }

        self::getLock($document)->release();
        Model\Tool\TmpStore::delete($document->getLockKey());
    }

    /**
     * @param string $html
     * @param array $params
     *
     * @return string
     */
    protected function processHtml($html, $params)
    {
        $placeholder = new \Pimcore\Placeholder();
        $document = $params['document'] ?? null;
        $hostUrl = $params['hostUrl'] ?? null;

        $html = $placeholder->replacePlaceholders($html, $params, $document);
        $twig = \Pimcore::getContainer()->get('twig');
        $template = $twig->createTemplate((string) $html);
        $html = $twig->render($template, $params);

        $html = \Pimcore\Helper\Mail::setAbsolutePaths($html, $document, $hostUrl);

        return $html;
    }

    protected function getLock(Document\PrintAbstract $document): LockInterface
    {
        if (!self::$lock) {
            self::$lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock($document->getLockKey());
        }

        return self::$lock;
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
    abstract public function getPdfFromString($html, $params = [], $returnFilePath = false);
}
