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

namespace Pimcore\Web2Print\Processor;

use Pimcore\Config;
use \Pimcore\Model\Document;
use Pimcore\Web2Print\Processor;
use Pimcore\Logger;

class WkHtmlToPdf extends Processor
{

    /**
     * @var string
     */
    private $wkhtmltopdfBin;

    /**
     * @var string
     */
    private $options = "";


    /**
     * @param string $wkhtmltopdfBin
     * @param array $options key => value
     */
    public function __construct($wkhtmltopdfBin = null, $options = null)
    {
        $web2printConfig = Config::getWeb2PrintConfig();

        if (empty($wkhtmltopdfBin)) {
            $this->wkhtmltopdfBin = $web2printConfig->wkhtmltopdfBin;
        } else {
            $this->wkhtmltopdfBin = $wkhtmltopdfBin;
        }

        if (empty($options)) {
            if ($web2printConfig->wkhtml2pdfOptions) {
                $options = $web2printConfig->wkhtml2pdfOptions->toArray();
            }
        }

        if ($options) {
            foreach ($options as $key => $value) {
                $this->options .= " --" . (string)$key;
                if ($value !== null && $value !== "") {
                    $this->options .= " " . (string)$value;
                }
            }
        } else {
            $this->options = "";
        }
    }

    /**
     * @param Document\PrintAbstract $document
     * @param $config
     * @return string
     * @throws \Exception
     */
    protected function buildPdf(Document\PrintAbstract $document, $config)
    {
        $web2printConfig = Config::getWeb2PrintConfig();

        $params = [];
        $this->updateStatus($document->getId(), 10, "start_html_rendering");
        $html = $document->renderDocument($params);
        $placeholder = new \Pimcore\Placeholder();
        $html = $placeholder->replacePlaceholders($html);
        $html = \Pimcore\Helper\Mail::setAbsolutePaths($html, $document, $web2printConfig->wkhtml2pdfHostname);

        $this->updateStatus($document->getId(), 40, "finished_html_rendering");

        file_put_contents(PIMCORE_TEMPORARY_DIRECTORY . DIRECTORY_SEPARATOR . "wkhtmltorpdf-input.html", $html);

        $this->updateStatus($document->getId(), 45, "saved_html_file");

        try {
            $this->updateStatus($document->getId(), 50, "pdf_conversion");

            $pdf = $this->fromStringToStream($html);

            $this->updateStatus($document->getId(), 100, "saving_pdf_document");
        } catch (\Exception $e) {
            Logger::error($e);
            $document->setLastGenerateMessage($e->getMessage());
            throw new \Exception("Error during REST-Request:" . $e->getMessage());
        }

        $document->setLastGenerateMessage("");

        return $pdf;
    }

    /**
     * @return array
     */
    public function getProcessingOptions()
    {
        return [];
    }

    /**
     * @param string $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $htmlString
     * @param string $dstFile
     * @return string
     */
    public function fromStringToFile($htmlString, $dstFile = null)
    {
        $id = uniqid();
        $tmpHtmlFile = PIMCORE_TEMPORARY_DIRECTORY . DIRECTORY_SEPARATOR . $id . ".htm";
        file_put_contents($tmpHtmlFile, $htmlString);
        $srcUrl = $this->getTempFileUrl() . basename($tmpHtmlFile);

        $pdfFile = $this->convert($srcUrl, $dstFile);

        @unlink($tmpHtmlFile);

        return $pdfFile;
    }

    /**
     * @param string $htmlString
     * @return string
     */
    public function fromStringToStream($htmlString)
    {
        $tmpFile = $this->fromStringToFile($htmlString);
        $stream = file_get_contents($tmpFile);
        @unlink($tmpFile);

        return $stream;
    }


    /**
     * @param string $srcUrl
     * @param string $dstFile
     * @return string
     * @throws \Exception
     */
    protected function convert($srcUrl, $dstFile = null)
    {
        $outputFile = PIMCORE_TEMPORARY_DIRECTORY . DIRECTORY_SEPARATOR . "wkhtmltopdf.out";
        if (empty($dstFile)) {
            $dstFile = PIMCORE_TEMPORARY_DIRECTORY . DIRECTORY_SEPARATOR . uniqid() . ".pdf";
        }

        if (empty($srcUrl) || empty($dstFile) || empty($this->wkhtmltopdfBin)) {
            throw new \Exception("srcUrl || dstFile || wkhtmltopdfBin is empty!");
        }

        $retVal = 0;
        $cmd = $this->wkhtmltopdfBin . " " . $this->options . " " . escapeshellarg($srcUrl) . " " . escapeshellarg($dstFile) . " > " . $outputFile;
        system($cmd, $retVal);
        $output = file_get_contents($outputFile);
        @unlink($outputFile);

        if ($retVal != 0 && $retVal != 1) {
            throw new \Exception("wkhtmltopdf reported error (" . $retVal . "): \n" . $output . "\ncommand was:" . $cmd);
        }

        return $dstFile;
    }

    /**
     * @return string
     */
    public static function getTempFileUrl()
    {
        $web2printConfig = Config::getWeb2PrintConfig();
        if ($web2printConfig->wkhtml2pdfHostname) {
            return $web2printConfig->wkhtml2pdfHostname . "/website/var/tmp/";
        } elseif (\Pimcore\Config::getSystemConfig()->general->domain) {
            $hostname = \Pimcore\Config::getSystemConfig()->general->domain;
        } else {
            $hostname = $_SERVER["HTTP_HOST"];
        }

        $protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';

        return $protocol . "://" . $hostname . "/website/var/tmp/";
    }
}
