<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;
use Pimcore\Model\Element\Note;

class Consent implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var bool
     */
    protected bool $consent = false;

    /**
     * @var int|null
     */
    protected ?int $noteId = null;

    /**
     * @var Note|null
     */
    protected ?Note $note = null;

    /**
     * @param bool $consent
     * @param int|null $noteId
     */
    public function __construct(bool $consent = false, int $noteId = null)
    {
        $this->consent = $consent;
        $this->noteId = $noteId;
        $this->markMeDirty();
    }

    /**
     * @return bool
     */
    public function getConsent(): bool
    {
        return $this->consent;
    }

    /**
     * @param bool $consent
     */
    public function setConsent(bool $consent): void
    {
        if ($consent != $this->consent) {
            $this->consent = $consent;
            $this->markMeDirty();
        }
    }

    /**
     * @return int|null
     */
    public function getNoteId(): ?int
    {
        return $this->noteId;
    }

    /**
     * @param int $noteId
     */
    public function setNoteId(int $noteId): void
    {
        if ($noteId != $this->noteId) {
            $this->noteId = $noteId;
            $this->markMeDirty();
        }
    }

    /**
     * @return Note|null
     */
    public function getNote(): ?Note
    {
        if (empty($this->note) && !empty($this->noteId)) {
            $this->note = Note::getById($this->noteId);
        }

        return $this->note;
    }

    /**
     * @param Note $note
     */
    public function setNote(Note $note): void
    {
        $this->note = $note;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getSummaryString(): string
    {
        $note = $this->getNote();
        if ($note) {
            return $note->getTitle() . ': ' . date('r', $note->getDate());
        }

        return '';
    }
}
