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
    /**
     * @var int
     */
    public $lastGenerated;

    /**
     * @var string
     */
    public $lastGenerateMessage;

    /**
     * @var string
     */
    public $controller = "web2print";

    /**
     * @param \Zend_Date $lastGenerated
     */
    public function setLastGeneratedDate(\Zend_Date $lastGenerated)
    {
        $this->lastGenerated = $lastGenerated->get(\Zend_Date::TIMESTAMP);
    }

    /**
     * @return null|\Zend_Date
     */
    public function getLastGeneratedDate()
    {
        if ($this->lastGenerated) {
            return new \Zend_Date($this->lastGenerated, \Zend_Date::TIMESTAMP);
        }

        return null;
    }

    /**
     * @return null|TmpStore
     */
    public function getInProgress()
    {
        return TmpStore::get($this->getLockKey());
    }

    /**
     * @param $lastGenerated
     */
    public function setLastGenerated($lastGenerated)
    {
        $this->lastGenerated = $lastGenerated;
    }

    /**
     * @return int
     */
    public function getLastGenerated()
    {
        return $this->lastGenerated;
    }

    /**
     * @param $lastGenerateMessage
     */
    public function setLastGenerateMessage($lastGenerateMessage)
    {
        $this->lastGenerateMessage = $lastGenerateMessage;
    }

    /**
     * @return string
     */
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

    /**
     * @param $params
     * @return string
     */
    public function renderDocument($params)
    {
        $html = Document\Service::render($this, $params, true);

        return $html;
    }

    /**
     * @return string
     */
    public function getPdfFileName()
    {
        return PIMCORE_TEMPORARY_DIRECTORY . DIRECTORY_SEPARATOR . "web2print-document-" . $this->getId() . ".pdf";
    }

    /**
     * @return bool
     */
    public function pdfIsDirty()
    {
        return $this->getLastGenerated() < $this->getModificationDate();
    }

    /**
     * @return string
     */
    public function getLockKey()
    {
        return "web2print_pdf_generation_" . $this->getId();
    }
}
