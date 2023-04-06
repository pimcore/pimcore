<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\WebToPrintBundle;

use Pimcore\Bundle\WebToPrintBundle\Event\DocumentEvents;
use Pimcore\Bundle\WebToPrintBundle\Exception\CancelException;
use Pimcore\Bundle\WebToPrintBundle\Exception\NotPreparedException;
use Pimcore\Bundle\WebToPrintBundle\Messenger\GenerateWeb2PrintPdfMessage;
use Pimcore\Bundle\WebToPrintBundle\Model\Document\PrintAbstract;
use Pimcore\Bundle\WebToPrintBundle\Processor\Chromium;
use Pimcore\Bundle\WebToPrintBundle\Processor\Gotenberg;
use Pimcore\Bundle\WebToPrintBundle\Processor\PdfReactor;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Helper\Mail;
use Pimcore\Logger;
use Pimcore\Model;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Twig\Sandbox\SecurityError;

abstract class Processor
{
    private static ?LockInterface $lock = null;

    public static function getInstance(): PdfReactor|Gotenberg|Chromium|Processor
    {
        $config = Config::getWeb2PrintConfig();

        return match ($config['generalTool']) {
            'pdfreactor' => new PdfReactor(),
            'chromium' => new Chromium(),
            'gotenberg' => new Gotenberg(),
            default => throw new \Exception('Invalid Configuration - ' . $config['generalTool'])
        };
    }

    /**
     * @param int $documentId
     * @param array $config
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function preparePdfGeneration(int $documentId, array $config): bool
    {
        $document = $this->getPrintDocument($documentId);
        if (Model\Tool\TmpStore::get($document->getLockKey())) {
            throw new \Exception('Process with given document already running.');
        }
        Model\Tool\TmpStore::add($document->getLockKey(), true);

        $jobConfig = new \stdClass();
        $jobConfig->documentId = $documentId;
        $jobConfig->config = $config;

        $this->saveJobConfigObjectFile($jobConfig);
        $this->updateStatus($documentId, 0, 'prepare_pdf_generation');

        $disableBackgroundExecution = $config['disableBackgroundExecution'] ?? false;

        if (!$disableBackgroundExecution) {
            \Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                new GenerateWeb2PrintPdfMessage($jobConfig->documentId)
            );

            return true;
        }

        return (bool)self::getInstance()->startPdfGeneration($jobConfig->documentId);
    }

    /**
     * @param int $documentId
     *
     * @return string|null
     *
     * @throws Model\Element\ValidationException
     * @throws NotPreparedException
     */
    public function startPdfGeneration(int $documentId): ?string
    {
        $jobConfigFile = $this->loadJobConfigObject($documentId);
        if (!$jobConfigFile) {
            throw new NotPreparedException('PDF Generation for document ' . $documentId . ' is not prepared.');
        }

        $document = $this->getPrintDocument($documentId);

        $lock = $this->getLock($document);
        // check if there is already a generating process running, wait if so ...
        $lock->acquire(true);

        $pdf = null;

        try {
            $preEvent = new DocumentEvent($document, [
                'processor' => $this,
                'jobConfig' => $jobConfigFile->config,
            ]);
            \Pimcore::getEventDispatcher()->dispatch($preEvent, DocumentEvents::PRINT_PRE_PDF_GENERATION);

            $pdf = $this->buildPdf($document, $jobConfigFile->config);
            file_put_contents($document->getPdfFileName(), $pdf);

            $postEvent = new DocumentEvent($document, [
                'filename' => $document->getPdfFileName(),
                'pdf' => $pdf,
            ]);
            \Pimcore::getEventDispatcher()->dispatch($postEvent, DocumentEvents::PRINT_POST_PDF_GENERATION);

            $document->setLastGenerated((time() + 1));
            $document->setLastGenerateMessage('');
            $document->save();
        } catch (CancelException $e) {
            Logger::debug($e->getMessage());
        } catch (\Exception $e) {
            Logger::err((string) $e);
            $document->setLastGenerateMessage($e->getMessage());
            $document->save();
        }

        $lock->release();
        Model\Tool\TmpStore::delete($document->getLockKey());

        @unlink(static::getJobConfigFile($documentId));

        return $pdf;
    }

    /**
     * @param PrintAbstract $document
     * @param object $config
     *
     * @return string
     *
     * @throws \Exception
     */
    abstract protected function buildPdf(PrintAbstract $document, object $config): string;

    protected function saveJobConfigObjectFile(\stdClass $jobConfig): bool
    {
        file_put_contents(static::getJobConfigFile($jobConfig->documentId), json_encode($jobConfig));

        return true;
    }

    protected function loadJobConfigObject(int $documentId): ?\stdClass
    {
        $file = static::getJobConfigFile($documentId);
        if (file_exists($file)) {
            return json_decode(file_get_contents($file));
        }

        return null;
    }

    /**
     * @param int $documentId
     *
     * @return PrintAbstract
     *
     * @throws \Exception
     */
    protected function getPrintDocument(int $documentId): PrintAbstract
    {
        $document = PrintAbstract::getById($documentId);
        if (empty($document)) {
            throw new \Exception('PrintDocument with ' . $documentId . ' not found.');
        }

        return $document;
    }

    public static function getJobConfigFile(int $processId): string
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'pdf-creation-job-' . $processId . '.json';
    }

    abstract public function getProcessingOptions(): array;

    /**
     * @param int $documentId
     * @param int $status
     * @param string $statusUpdate
     *
     * @throws CancelException
     */
    protected function updateStatus(int $documentId, int $status, string $statusUpdate): void
    {
        $jobConfig = $this->loadJobConfigObject($documentId);
        if (!$jobConfig) {
            throw new CancelException('PDF Generation for document ' . $documentId . ' is canceled.');
        }
        $jobConfig->status = $status;
        $jobConfig->statusUpdate = $statusUpdate;
        $this->saveJobConfigObjectFile($jobConfig);
    }

    public function getStatusUpdate(int $documentId): ?array
    {
        $jobConfig = $this->loadJobConfigObject($documentId);
        if ($jobConfig) {
            return [
                'status' => $jobConfig->status,
                'statusUpdate' => $jobConfig->statusUpdate,
            ];
        }

        return null;
    }

    /**
     * @param int $documentId
     *
     * @throws \Exception
     */
    public function cancelGeneration(int $documentId): void
    {
        $document = PrintAbstract::getById($documentId);
        if (empty($document)) {
            throw new \Exception('Document with id ' . $documentId . ' not found.');
        }

        $this->getLock($document)->release();
        Model\Tool\TmpStore::delete($document->getLockKey());
        @unlink(static::getJobConfigFile($documentId));
    }

    /**
     * @param string $html
     * @param array $params
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function processHtml(string $html, array $params): string
    {
        $document = $params['document'] ?? null;
        $hostUrl = $params['hostUrl'] ?? null;
        $templatingEngine = \Pimcore::getContainer()->get('pimcore.templating.engine.delegating');

        try {
            $twig = $templatingEngine->getTwigEnvironment(true);
            $template = $twig->createTemplate($html);

            $html = $twig->render($template, $params);
        } catch (SecurityError $e) {
            Logger::err((string) $e);

            throw new \Exception(sprintf('Failed rendering the print template: %s. Please check your twig sandbox security policy or contact the administrator.', $e->getMessage()));
        } finally {
            $templatingEngine->disableSandboxExtensionFromTwigEnvironment();
        }

        return Mail::setAbsolutePaths($html, $document, $hostUrl);
    }

    protected function getLock(PrintAbstract $document): LockInterface
    {
        if (!self::$lock) {
            self::$lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock($document->getLockKey());
        }

        return self::$lock;
    }

    /**
     * Returns the generated pdf file. Its path or data depending supplied parameter
     *
     * @param string $html
     * @param array $params
     * @param bool $returnFilePath return the path to the pdf file or the content
     *
     * @return string
     */
    abstract public function getPdfFromString(string $html, array $params = [], bool $returnFilePath = false): string;
}
