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

namespace Pimcore\Event\Model\Ecommerce\IndexService;

use Symfony\Component\EventDispatcher\Event;

class PreprocessErrorEvent extends Event
{
    /**
     * @var \Throwable
     */
    protected $exception;

    /**
     * @var bool
     */
    protected $throwException;

    /**
     * @var int
     */
    protected $subObjectId;

    /**
     * PreprocessErrorEvent constructor.
     *
     * @param \Throwable $exception
     * @param bool $throwException
     * @param int $subObjectId
     */
    public function __construct(\Throwable $exception, bool $throwException = true, int $subObjectId = 0)
    {
        $this->exception = $exception;
        $this->throwException = $throwException;
        $this->subObjectId = $subObjectId;
    }

    /**
     * @return \Throwable
     */
    public function getException(): \Throwable
    {
        return $this->exception;
    }

    /**
     * @param bool $throwException
     */
    public function setThrowException(bool $throwException): void
    {
        $this->throwException = $throwException;
    }

    /**
     * @return bool
     */
    public function doThrowException(): bool
    {
        return $this->throwException;
    }

    /**
     * @return int
     */
    public function getSubObjectId(): int
    {
        return $this->subObjectId;
    }

    /**
     * @param int $subObjectId
     */
    public function setSubObjectId(int $subObjectId): void
    {
        $this->subObjectId = $subObjectId;
    }
}
