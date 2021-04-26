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

namespace Pimcore\Workflow\Notes;

use Pimcore\Model\Element\ElementInterface;

interface CustomHtmlServiceInterface
{
    /**
     * Render custom HTML for the default position, which is typically controlled by the workflow configuration file.
     * Implement this method to render custom HTML for the default position (=most common case)
     *
     * @param ElementInterface $element
     *
     * @return string the custom HTML markup as a string.
     */
    public function renderHtml(ElementInterface $element): string;

    /**
     * Render custom HTML for a specific position within the workflow note modals.
     * Implement this method if you need full control of the rendering process, and you want to show HTML
     * on multiple positions for an element.
     *
     * @param ElementInterface $element
     * @param string $requestedPosition the requested position for which content should be rendered.
     *
     * @return string the HTML markup or an empty string, if for the requested position nothing should be rendered.
     */
    public function renderHtmlForRequestedPosition(ElementInterface $element, string $requestedPosition): string;
}
