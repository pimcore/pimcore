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

namespace Pimcore\Workflow\Notes;

/**
 * @method string getLabel()
 *
 * @property array $options
 */
trait NotesAwareTrait
{
    protected ?CustomHtmlServiceInterface $customHtmlService = null;

    public function getNotes(): ?array
    {
        if ($this->getNotesCommentEnabled() || $this->getCustomHtmlService()) {
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

    /**
     * Inject service via compiler pass.
     *
     */
    public function setCustomHtmlService(CustomHtmlServiceInterface $customHtmlService): void
    {
        if ($customHtmlService instanceof AbstractCustomHtmlService) {
            if ($this->getName() == $customHtmlService->getTransitionName()) {
                $this->customHtmlService = $customHtmlService;
            } elseif ($this->getName() == $customHtmlService->getActionName()) {
                $this->customHtmlService = $customHtmlService;
            }
        }
    }

    public function getCustomHtmlService(): ?CustomHtmlServiceInterface
    {
        return $this->customHtmlService;
    }
}
