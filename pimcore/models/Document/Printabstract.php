<?php

namespace Pimcore\Model\Document;

use \Pimcore\Model\Document;
use Pimcore\Web2Print\Processor;

abstract class PrintAbstract extends Document\PageSnippet {

    public $lastGenerated;
    public $lastGenerateMessage;

    public function setLastGeneratedDate(\Zend_Date $lastGenerated)
    {
        $this->lastGenerated = $lastGenerated->get(\Zend_Date::TIMESTAMP);
    }

    public function getLastGeneratedDate()
    {
        if($this->lastGenerated) {
            return new \Zend_Date($this->lastGenerated, \Zend_Date::TIMESTAMP);
        }
        return null;
    }

    public function getInProgress()
    {
        return \Pimcore\Model\Tool\Lock::isLocked($this->getLockKey(), 0);
    }

    public function setLastGenerated($lastGenerated)
    {
        $this->lastGenerated = $lastGenerated;
    }

    public function getLastGenerated()
    {
        return $this->lastGenerated;
    }

    public function setLastGenerateMessage($lastGenerateMessage)
    {
        $this->lastGenerateMessage = $lastGenerateMessage;
    }

    public function getLastGenerateMessage()
    {
        return $this->lastGenerateMessage;
    }


    /**
     * @param $config
     */
    public function generatePdf($config) {
        Processor::getInstance()->preparePdfGeneration($this->getId(), $config);
    }

    public function renderDocument($params) {
        $html = Document\Service::render($this, $params, true);
        return $html;
    }

    public function getPdfFileName() {
        return PIMCORE_TEMPORARY_DIRECTORY . DIRECTORY_SEPARATOR . "web2print-document-" . $this->getId() . ".pdf";
    }

    public function pdfIsDirty() {
        return $this->getLastGenerated() < $this->getModificationDate();
    }

    public function getLockKey() {
        return "web2print_pdf_generation_" . $this->getId();
    }

}
