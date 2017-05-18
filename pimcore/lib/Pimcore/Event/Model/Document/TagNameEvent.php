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

namespace Pimcore\Event\Model\Document;

use Pimcore\Document\Tag\Block\BlockState;
use Pimcore\Model\Document;
use Symfony\Component\EventDispatcher\Event;

class TagNameEvent extends Event
{
    /**
     * Tag type (e.g. "input")
     *
     * @var string
     */
    private $type;

    /**
     * Tag name (e.g. "headline")
     *
     * @var string
     */
    private $inputName;

    /**
     * The current block state
     *
     * @var BlockState
     */
    private $blockState;

    /**
     * The built tag name
     *
     * @var string
     */
    private $tagName;

    /**
     * @var Document
     */
    private $document;

    /**
     * @param string $type
     * @param string $inputName
     * @param BlockState $blockState
     * @param string $tagName
     * @param Document $document
     */
    public function __construct(
        string $type,
        string $inputName,
        BlockState $blockState,
        string $tagName,
        Document $document
    )
    {
        $this->type       = $type;
        $this->inputName  = $inputName;
        $this->blockState = $blockState;
        $this->tagName    = $tagName;
        $this->document   = $document;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getInputName(): string
    {
        return $this->inputName;
    }

    /**
     * @return BlockState
     */
    public function getBlockState(): BlockState
    {
        return $this->blockState;
    }

    /**
     * @return Document
     */
    public function getDocument(): Document
    {
        return $this->document;
    }

    /**
     * @return string
     */
    public function getTagName(): string
    {
        return $this->tagName;
    }

    /**
     * @param string $tagName
     */
    public function setTagName(string $tagName)
    {
        $this->tagName = $tagName;
    }
}
