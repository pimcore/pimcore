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

namespace Pimcore\Workflow\Notes;

interface NotesAwareInterface
{
    public function getNotes(): ?array;

    public function getNotesCommentRequired(): bool;

    public function getNotesCommentEnabled(): bool;

    public function getNotesCommentSetterFn(): ?string;

    public function getNotesType(): string;

    public function getNotesTitle(): string;

    public function getNotesAdditionalFields(): array;

    public function getCustomHtmlService(): ?CustomHtmlServiceInterface;
}
