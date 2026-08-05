<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Event\Cache\Core;

use Symfony\Contracts\EventDispatcher\Event;

class ResultEvent extends Event
{
    protected bool $result;

    public function __construct(bool $result = true)
    {
        $this->setResult($result);
    }

    public function getResult(): bool
    {
        return $this->result;
    }

    public function setResult(bool $result): void
    {
        $this->result = $result;
    }
}
