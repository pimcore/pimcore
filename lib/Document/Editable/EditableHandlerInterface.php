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

namespace Pimcore\Document\Editable;

use Pimcore\Model\Document\Editable;
use Pimcore\Model\Document\Editable\Area\Info;
use Pimcore\Templating\Model\ViewModelInterface;

/**
 * @deprecated will be removed in v7, use EditableHandler directly instead
 */
interface EditableHandlerInterface
{
    /**
     * Determine if handler supports the tag
     *
     * @param ViewModelInterface $view
     *
     * @return bool
     */
    public function supports($view);

    /**
     * Determines if a brick is enabled
     *
     * @param Editable $tag
     * @param string $brick
     *
     * @return bool
     */
    public function isBrickEnabled(Editable $tag, $brick);

    /**
     * Get available areas for an areablock
     *
     * @param Editable\Areablock $tag
     * @param array $options
     *
     * @return array
     */
    public function getAvailableAreablockAreas(Editable\Areablock $tag, array $options);

    /**
     * Render the area frontend
     *
     * @param Info $info
     */
    public function renderAreaFrontend(Info $info);

    /**
     * Render a sub-action (snippet, renderlet)
     *
     * @param ViewModelInterface $view
     * @param string $controller
     * @param string $action
     * @param string|null $parent Bundle
     * @param array $attributes
     * @param array $query
     * @param array $options
     *
     * @return string
     */
    public function renderAction($view, $controller, $action, $parent = null, array $attributes = [], array $query = [], array $options = []);
}

class_alias(EditableHandlerInterface::class, 'Pimcore\Document\Tag\TagHandlerInterface');
