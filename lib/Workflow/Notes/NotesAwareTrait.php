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

namespace Pimcore\Workflow\Notes;

/**
 * @method getLabel()
 *
 * @property array $options
 */
trait NotesAwareTrait
{
    public function getNotes(): ?array
    {
        if ($this->getNotesCommentEnabled()) {
            return $this->options['notes'];
        }

        return null;
    }

    public function getNotesCommentRequired(): bool
    {
        return isset($this->options['notes']['commentRequired']) && $this->options['notes']['commentRequired'];
    }

    public function getNotesCommentEnabled(): bool
    {
        return isset($this->options['notes']['commentEnabled']) && $this->options['notes']['commentEnabled'];
    }

    public function getNotesCommentSetterFn(): ?string
    {
        return $this->options['notes']['commentSetterFn'] ?? null;
    }

    public function getNotesType(): string
    {
        return $this->options['notes']['type'] ?? 'Status update';
    }

    public function getNotesTitle(): string
    {
        return $this->options['notes']['title'] ?? $this->getLabel();
    }

    public function getNotesAdditionalFields(): array
    {
        return $this->options['notes']['additionalFields'] ?? [];
    }
}
