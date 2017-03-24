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

namespace Pimcore\Document\Tag;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;
use Pimcore\Model\Document\Tag;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\View;

interface TagHandlerInterface
{
    /**
     * Determine if handler supports the tag
     *
     * @param ViewModelInterface|View $view
     * @return bool
     */
    public function supports($view);

    /**
     * Determines if a brick is enabled
     *
     * @param Tag $tag
     * @param string $brick
     *
     * @return bool
     */
    public function isBrickEnabled(Tag $tag, $brick);

    /**
     * Get available areas for an areablock
     *
     * @param Tag\Areablock $tag
     * @param array $options
     *
     * @return array
     */
    public function getAvailableAreablockAreas(Tag\Areablock $tag, array $options);

    /**
     * Render the area frontend
     *
     * @param Info $info
     * @param array $params
     */
    public function renderAreaFrontend(Info $info, array $params);

    /**
     * Render a sub-action (snippet, renderlet)
     *
     * @param ViewModelInterface|View $view
     * @param string $controller
     * @param string $action
     * @param string|null $parent Bundle or module (legacy) name
     * @param array $params
     * @return string
     */
    public function renderAction($view, $controller, $action, $parent = null, array $params = []);
}
