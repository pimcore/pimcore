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

use Pimcore\Model\Element\ElementInterface;

interface CustomHtmlServiceInterface
{
    /**
     * Render custom HTML for the default position, which is typically controlled by the workflow configuration file.
     * Implement this method to render custom HTML for the default position (=most common case)
     *
     *
     * @return string the custom HTML markup as a string.
     */
    public function renderHtml(ElementInterface $element): string;

    /**
     * Render custom HTML for a specific position within the workflow note modals.
     * Implement this method if you need full control of the rendering process, and you want to show HTML
     * on multiple positions for an element.
     *
     * @param string $requestedPosition the requested position for which content should be rendered.
     *
     * @return string the HTML markup or an empty string, if for the requested position nothing should be rendered.
     */
    public function renderHtmlForRequestedPosition(ElementInterface $element, string $requestedPosition): string;
}
