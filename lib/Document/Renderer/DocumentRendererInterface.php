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

namespace Pimcore\Document\Renderer;

use Pimcore\Model\Document;

interface DocumentRendererInterface
{
    /**
     * Renders document and returns rendered result as string
     *
     * @param Document\PageSnippet $document
     * @param array $attributes
     * @param array $query
     * @param array $options
     *
     * @return string
     */
    public function render(Document\PageSnippet $document, array $attributes = [], array $query = [], array $options = []): string;
}
