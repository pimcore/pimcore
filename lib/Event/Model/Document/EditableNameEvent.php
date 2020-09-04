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

use Pimcore\Document\Editable\Block\BlockState;
use Pimcore\Model\Document;
use Symfony\Component\EventDispatcher\Event;

class EditableNameEvent extends Event
{
    /**
     * Editable type (e.g. "input")
     *
     * @var string
     */
    private $type;

    /**
     * Editable name (e.g. "headline")
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
     * The built editable name
     *
     * @var string
     */
    private $editableName;

    /**
     * @var Document
     */
    private $document;

    /**
     * @param string $type
     * @param string $inputName
     * @param BlockState $blockState
     * @param string $editableName
     * @param Document $document
     */
    public function __construct(
        string $type,
        string $inputName,
        BlockState $blockState,
        string $editableName,
        Document $document
    ) {
        $this->type = $type;
        $this->inputName = $inputName;
        $this->blockState = $blockState;
        $this->editableName = $editableName;
        $this->document = $document;
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
    public function getEditableName(): string
    {
        return $this->editableName;
    }

    /**
     * @param string $editableName
     */
    public function setEditableName(string $editableName)
    {
        $this->editableName = $editableName;
    }

    /**
     * @return string
     *
     * @deprecated since 6.8 and will be removed in 7. use getEditableName() instead.
     */
    public function getTagName(): string
    {
        return $this->getEditableName();
    }

    /**
     * @param string $tagName
     *
     * @deprecated since 6.8 and will be removed in 7. use setEditableName() instead.
     */
    public function setTagName(string $tagName)
    {
        $this->setEditableName($tagName);
    }
}

class_alias(EditableNameEvent::class, 'Pimcore\Event\Model\Document\TagNameEvent');
