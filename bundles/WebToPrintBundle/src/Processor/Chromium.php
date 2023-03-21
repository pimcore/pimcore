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

namespace Pimcore\Bundle\WebToPrintBundle\Processor;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\BrowserConnectionFailed;
use Pimcore\Bundle\WebToPrintBundle\Config;
use Pimcore\Bundle\WebToPrintBundle\Event\Model\PrintConfigEvent;
use Pimcore\Bundle\WebToPrintBundle\Model\Document\PrintAbstract;
use Pimcore\Bundle\WebToPrintBundle\Processor;
use Pimcore\Bundle\WebToPrintBundle\Event\DocumentEvents;
use Pimcore\Logger;
use Pimcore\Image\Chromium as ChromiumLib;

class Chromium extends Processor
{
    /**
     * @internal
     */
    protected function buildPdf(PrintAbstract $document, object $config): string
    {
        $web2printConfig = Config::getWeb2PrintConfig();
        $chromiumConfig = $web2printConfig['chromiumSettings'];
        $chromiumConfig = json_decode($chromiumConfig, true);

        $params = [
            'document' => $document
        ];



        $this->updateStatus($document->getId(), 10, 'start_html_rendering');
        $html = $document->renderDocument($params);

        if (isset($web2printConfig['chromiumHostUrl'])) {
            $params['hostUrl'] = $web2printConfig['chromiumHostUrl'];
        }

        $html = $this->processHtml($html, $params);
        $this->updateStatus($document->getId(), 40, 'finished_html_rendering');

        if ($chromiumConfig) {
            foreach (['header', 'footer'] as $item) {
                if (key_exists($item, $chromiumConfig) && $chromiumConfig[$item] &&
                    $content = file_get_contents($chromiumConfig[$item])) {
                    $chromiumConfig[$item . 'Template'] = $content;
                }
                unset($chromiumConfig[$item]);
            }
        }

        try {
            $this->updateStatus($document->getId(), 50, 'pdf_conversion');
            $pdf = $this->getPdfFromString($html, $chromiumConfig ?? []);
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
    public function getProcessingOptions(): array
    {
        $event = new PrintConfigEvent($this, [
            'options' => [],
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::PRINT_MODIFY_PROCESSING_OPTIONS);

        return (array)$event->getArgument('options');
    }

    /**
     * @internal
     */
    public function getPdfFromString(string $html, array $params = [], bool $returnFilePath = false): string
    {
        $params = $params ?: $this->getDefaultOptions();

        $event = new PrintConfigEvent($this, [
            'params' => $params,
            'html' => $html,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::PRINT_MODIFY_PROCESSING_CONFIG);

        ['html' => $html, 'params' => $params] = $event->getArguments();


        $chromiumUri = \Pimcore\Config::getSystemConfiguration('chromium')['uri'];

        if (!empty($chromiumUri)){
            $browser = BrowserFactory::connectToBrowser($chromiumUri);
        }else{
            $browserFactory = new BrowserFactory(ChromiumLib::getChromiumBinary());
            // starts headless chrome
            $browser = $browserFactory->createBrowser([
                'noSandbox' => true,
                'startupTimeout' => 120,
                'enableImages' => true,
                'ignoreCertificateErrors' => true
            ]);
        }


        try {
            $page = $browser->createPage();
            $page->setHtml($html, 5000);

            $pdf = $page->pdf($params);
            if ($returnFilePath) {
                $path = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . uniqid('web2print_') . '.pdf';
                $pdf->saveToFile($path);
                $output = $path;
            }else {
                $output = base64_decode($pdf->getBase64());
            }
        } catch (\Throwable $e) {
            Logger::debug('Could not create pdf with chromium: '. print_r($e, true));
            $output = (string) $e;
        } finally {
            $browser->close();
        }

        return $output;
    }

    private function getDefaultOptions(): array
    {
        return [
            'landscape' => false,
            'printBackground' => false,
            'displayHeaderFooter' => false,
            'preferCSSPageSize' => false,
            'marginTop' => 0.4, //must be a float, value in inches
            'marginBottom' => 0.4,//must be a float, value in inches
            'marginLeft' => 0.4,//must be a float, value in inches
            'marginRight' => 0.4,//must be a float, value in inches
            'paperWidth' => 8.5, //must be a float, value in inches
            'paperHeight' => 11.0,//must be a float, value in inches
            'headerTemplate' => '',
            'footerTemplate' => '',
            'scale' => 1.0, // must be a float
            //'pageRanges'
            //'ignoreInvalidPageRanges'
        ];
    }

}
