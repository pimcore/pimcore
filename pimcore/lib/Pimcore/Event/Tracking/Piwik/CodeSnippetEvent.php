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

namespace Pimcore\Event\Tracking\Piwik;

use Pimcore\Model\Site;
use Symfony\Component\EventDispatcher\Event;

class CodeSnippetEvent extends Event
{
    /**
     * @var array
     */
    private $parts = [];

    /**
     * @var Site|null
     */
    private $site;

    public function __construct(array $parts, Site $site = null)
    {
        $this->setParts($parts);
        $this->site = $site;
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    public function setParts(array $parts)
    {
        $this->parts = [];

        foreach ($parts as $part) {
            $this->addPart($part);
        }
    }

    public function addPart(string $part)
    {
        $this->parts[] = $part;
    }

    /**
     * @return null|Site
     */
    public function getSite()
    {
        return $this->site;
    }
}
