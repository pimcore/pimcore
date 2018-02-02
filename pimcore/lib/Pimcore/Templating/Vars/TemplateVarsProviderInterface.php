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

namespace Pimcore\Templating\Vars;

use Symfony\Component\HttpFoundation\Request;

interface TemplateVarsProviderInterface
{
    /**
     * Adds template vars which should be included in a new view model when
     * created via TemplateVarsResolver.
     *
     * @param Request $request
     * @param array $templateVars
     *
     * @return array
     */
    public function addTemplateVars(Request $request, array $templateVars);
}
