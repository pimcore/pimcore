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
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document;

use \Pimcore\Model\Document;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Web2Print\Processor;

/**
 * @method \Pimcore\Model\Document\PrintAbstract\Dao getDao()
 */
abstract class PrintAbstract extends Document\PageSnippet
{
    public $lastGenerated;
    public $lastGenerateMessage;

    /**
     * @var string
     */
    public $controller = "web2print";

    public function setLastGeneratedDate(\Zend_Date $lastGenerated)
    {
        $this->lastGenerated = $lastGenerated->get(\Zend_Date::TIMESTAMP);
    }

    public function getLastGeneratedDate()
    {
        if ($this->lastGenerated) {
            return new \Zend_Date($this->lastGenerated, \Zend_Date::TIMESTAMP);
        }

        return null;
    }

    public function getInProgress()
    {
        return TmpStore::get($this->getLockKey());
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
    public function generatePdf($config)
    {
        Processor::getInstance()->preparePdfGeneration($this->getId(), $config);
    }

    public function renderDocument($params)
    {
        $html = Document\Service::render($this, $params, true);

        return $html;
    }

    public function getPdfFileName()
    {
        return PIMCORE_TEMPORARY_DIRECTORY . DIRECTORY_SEPARATOR . "web2print-document-" . $this->getId() . ".pdf";
    }

    public function pdfIsDirty()
    {
        return $this->getLastGenerated() < $this->getModificationDate();
    }

    public function getLockKey()
    {
        return "web2print_pdf_generation_" . $this->getId();
    }
}
