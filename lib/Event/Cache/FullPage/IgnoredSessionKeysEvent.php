<?php

declare(strict_types=1);

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

namespace Pimcore\Event\Cache\FullPage;

use Symfony\Component\EventDispatcher\Event;

class IgnoredSessionKeysEvent extends Event
{
    /**
     * Session keys which will be ignored when determining
     * if the full page cache should be disabled due to
     * existing session data.
     *
     * @var array
     */
    private $keys = [];

    /**
     * @param array $keys
     */
    public function __construct(array $keys = [])
    {
        $this->keys = $keys;
    }

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function setKeys(array $keys)
    {
        $this->keys = $keys;
    }
}
