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

namespace Pimcore\Event\Model;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class DataObjectImportEvent
 * @package Pimcore\Event\Model
 */
class DataObjectImportEvent extends Event
{
    /**
     * @var
     */
    protected $config;

    /**
     * @var
     */
    protected $originalFile;


    /**
     * @var Concrete
     */
    protected $object;


    /**
     * @var
     */
    protected $rowData;


    /**
     * @var
     */
    protected $additionalData;

    /**
     * @var
     */
    protected $context;


    /**
     * DataObjectImportEvent constructor.
     * @param $config
     * @param $originalFile
     */
    public function __construct($config, $originalFile)
    {
        $this->config = $config;
        $this->originalFile = $originalFile;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return mixed
     */
    public function getOriginalFile()
    {
        return $this->originalFile;
    }

    /**
     * @param mixed $originalFile
     */
    public function setOriginalFile($originalFile)
    {
        $this->originalFile = $originalFile;
    }

    /**
     * @return Concrete
     */
    public function getObject(): Concrete
    {
        return $this->object;
    }

    /**
     * @param Concrete $object
     */
    public function setObject(Concrete $object)
    {
        $this->object = $object;
    }

    /**
     * @return mixed
     */
    public function getRowData()
    {
        return $this->rowData;
    }

    /**
     * @param mixed $rowData
     */
    public function setRowData($rowData)
    {
        $this->rowData = $rowData;
    }

    /**
     * @return mixed
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }

    /**
     * @param mixed $additionalData
     */
    public function setAdditionalData($additionalData)
    {
        $this->additionalData = $additionalData;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }



}
