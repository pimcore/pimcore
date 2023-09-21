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

namespace Pimcore\Event\Model;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class DataObjectImportEvent
 *
 * @package Pimcore\Event\Model
 */
class DataObjectImportEvent extends Event
{
    protected mixed $config = null;

    protected string $originalFile;

    protected Concrete $object;

    protected mixed $rowData = null;

    protected mixed $additionalData = null;

    protected mixed $context = null;

    /**
     * DataObjectImportEvent constructor.
     *
     */
    public function __construct(mixed $config, string $originalFile)
    {
        $this->config = $config;
        $this->originalFile = $originalFile;
    }

    public function getConfig(): mixed
    {
        return $this->config;
    }

    public function setConfig(mixed $config): void
    {
        $this->config = $config;
    }

    public function getOriginalFile(): string
    {
        return $this->originalFile;
    }

    public function setOriginalFile(string $originalFile): void
    {
        $this->originalFile = $originalFile;
    }

    public function getObject(): Concrete
    {
        return $this->object;
    }

    public function setObject(Concrete $object): void
    {
        $this->object = $object;
    }

    public function getRowData(): mixed
    {
        return $this->rowData;
    }

    public function setRowData(mixed $rowData): void
    {
        $this->rowData = $rowData;
    }

    public function getAdditionalData(): mixed
    {
        return $this->additionalData;
    }

    public function setAdditionalData(mixed $additionalData): void
    {
        $this->additionalData = $additionalData;
    }

    public function getContext(): mixed
    {
        return $this->context;
    }

    public function setContext(mixed $context): void
    {
        $this->context = $context;
    }
}
