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
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
    protected $consent;

    /**
     * @var int
     */
    protected $noteId;

    /**
     * @var Note
     */
    protected $note;

    /**
     * Consent constructor.
     *
     * @param bool $consent
     * @param int $noteId
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
    public function setConsent(bool $consent)
    {
        if ($consent != $this->consent) {
            $this->consent = $consent;
            $this->markMeDirty();
        }
    }

    /**
     * @return int
     */
    public function getNoteId()
    {
        return $this->noteId;
    }

    /**
     * @param int $noteId
     */
    public function setNoteId(int $noteId)
    {
        if ($noteId != $this->noteId) {
            $this->noteId = $noteId;
            $this->markMeDirty();
        }
    }

    /**
     * @return Note
     */
    public function getNote()
    {
        if (empty($this->note) && !empty($this->noteId)) {
            $this->note = Note::getById($this->noteId);
        }

        return $this->note;
    }

    /**
     * @param Note $note
     */
    public function setNote(Note $note)
    {
        $this->note = $note;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getSummaryString()
    {
        $note = $this->getNote();
        if ($note) {
            return $note->getTitle() . ': ' . date('r', $note->getDate());
        } else {
            return '';
        }
    }
}
