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

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Element\Tag;
use Symfony\Component\EventDispatcher\Event;

class TagEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @var Tag
     */
    protected $tag;

    /**
     * TagEvent constructor.
     *
     * @param Tag $tag
     * @param array $arguments
     */
    public function __construct(Tag $tag, array $arguments = [])
    {
        $this->tag = $tag;
        $this->arguments = $arguments;
    }

    /**
     * @return Tag
     */
    public function getTag(): Tag
    {
        return $this->tag;
    }

    /**
     * @param Tag $tag
     */
    public function setTag(Tag $tag): void
    {
        $this->tag = $tag;
    }
}
