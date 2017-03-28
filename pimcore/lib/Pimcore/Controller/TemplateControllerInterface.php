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

namespace Pimcore\Controller;

use Symfony\Component\HttpFoundation\Request;

interface TemplateControllerInterface
{
    const ATTRIBUTE_TEMPLATE_CONTROLLER = '_template_controller';
    const ATTRIBUTE_AUTO_RENDER = '_template_controller_auto_render';
    const ATTRIBUTE_AUTO_RENDER_ENGINE = '_template_controller_auto_render_engine';

    /**
     * Enable view auto-rendering without depending on the Template annotation
     *
     * @param Request $request
     * @param bool $autoRender
     * @param string|null $engine
     */
    public function setViewAutoRender(Request $request, $autoRender, $engine = null);
}
