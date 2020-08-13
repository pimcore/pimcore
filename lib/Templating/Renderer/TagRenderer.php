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

namespace Pimcore\Templating\Renderer;

@trigger_error(sprintf('Class "%s" is deprecated since v6.8 and will be removed in 7. Use "%s" instead.', TagRenderer::class, EditableRenderer::class), E_USER_DEPRECATED);

class_exists(EditableRenderer::class);

if (false) {
    /**
     * @deprecated use \Pimcore\Templating\Renderer\EditableRenderer instead.
     */
    class TagRenderer extends EditableRenderer
    {
    }
}
