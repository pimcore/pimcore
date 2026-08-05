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
