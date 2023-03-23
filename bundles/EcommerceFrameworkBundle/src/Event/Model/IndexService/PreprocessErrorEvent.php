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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model\IndexService;

use Symfony\Contracts\EventDispatcher\Event;

class PreprocessErrorEvent extends Event
{
    protected \Throwable $exception;

    protected bool $throwException;

    protected int $subObjectId;

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

    public function getException(): \Throwable
    {
        return $this->exception;
    }

    public function setThrowException(bool $throwException): void
    {
        $this->throwException = $throwException;
    }

    public function doThrowException(): bool
    {
        return $this->throwException;
    }

    public function getSubObjectId(): int
    {
        return $this->subObjectId;
    }

    public function setSubObjectId(int $subObjectId): void
    {
        $this->subObjectId = $subObjectId;
    }
}
