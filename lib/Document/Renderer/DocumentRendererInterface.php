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

namespace Pimcore\Document\Renderer;

use Pimcore\Model\Document;

interface DocumentRendererInterface
{
    /**
     * Renders document and returns rendered result as string
     *
     *
     */
    public function render(Document\PageSnippet $document, array $attributes = [], array $query = [], array $options = []): string;
}
