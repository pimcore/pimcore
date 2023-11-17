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

namespace Pimcore\Event\Model\DataObject\ClassDefinition;

use Pimcore\Model\DataObject\ClassDefinition\Data\UrlSlug;
use Symfony\Contracts\EventDispatcher\Event;

class UrlSlugEvent extends Event
{
    protected ?UrlSlug $urlSlug;

    protected array $data;

    public function __construct(?UrlSlug $urlSlug, array $data)
    {
        $this->urlSlug = $urlSlug;
        $this->data = $data;
    }

    public function getUrlSlug(): ?UrlSlug
    {
        return $this->urlSlug;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
