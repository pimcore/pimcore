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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Element\Tag;
use Symfony\Contracts\EventDispatcher\Event;

class TagEvent extends Event
{
    use ArgumentsAwareTrait;

    protected Tag $tag;

    /**
     * TagEvent constructor.
     *
     */
    public function __construct(Tag $tag, array $arguments = [])
    {
        $this->tag = $tag;
        $this->arguments = $arguments;
    }

    public function getTag(): Tag
    {
        return $this->tag;
    }

    public function setTag(Tag $tag): void
    {
        $this->tag = $tag;
    }
}
