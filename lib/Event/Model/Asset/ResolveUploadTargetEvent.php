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

namespace Pimcore\Event\Model\Asset;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Component\EventDispatcher\Event;

class ResolveUploadTargetEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var array
     */
    protected $context;

    /**
     * @var int
     */
    protected $parentId;

    /**
     * ResolveUploadTargetEvent constructor.
     *
     * @param int $parentId
     * @param string $filename
     * @param array $context contextual information
     */
    public function __construct($parentId, string $filename, $context)
    {
        $this->parentId = $parentId;
        $this->filename = $filename;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param mixed $parentId
     */
    public function setParentId($parentId): void
    {
        $this->parentId = $parentId;
    }
}
