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

namespace Pimcore\Document\Tag\NamingStrategy\Migration\Analyze;

use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\AbstractBlock;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\Editable;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Exception\BuildEditableException;
use Pimcore\Model\Document;

interface ConflictResolverInterface
{
    /**
     * @param Document\PageSnippet $document
     * @param BuildEditableException $exception
     *
     * @return BuildEditableException
     */
    public function resolveBuildFailed(Document\PageSnippet $document, BuildEditableException $exception): BuildEditableException;

    /**
     * @param Document\PageSnippet $document
     * @param BuildEditableException $exception
     * @param AbstractBlock[] $blocks
     *
     * @return AbstractBlock
     */
    public function resolveBlockConflict(Document\PageSnippet $document, BuildEditableException $exception, array $blocks): AbstractBlock;

    /**
     * @param Document\PageSnippet $document
     * @param BuildEditableException $exception
     * @param Editable[] $editables
     *
     * @return Editable
     */
    public function resolveEditableConflict(Document\PageSnippet $document, BuildEditableException $exception, array $editables): Editable;
}
