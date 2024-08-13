<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Extension\Document\Areabrick;

/**
 * Bricks implementing this interface auto-resolve view templates if hasTemplate() properties are set.
 * Depending on the result of getTemplateLocation() and getTemplateSuffix() the tag handler builds the
 * following references:
 *
 * - @<bundle>/areas/<brickId>/view.<suffix>
 *      -> resolves to <bundle>/templates/areas/<brickId>/view.<suffix> (Symfony >= 5 structure)
 *         or <bundle>/Resources/views/areas/<brickId>/view.<suffix> (Symfony <= 4 structure)
 * - areas/<brickId>/view.<suffix>
 *      -> resolves to <project>/templates/areas/<brickId>/view.<suffix>
 */
interface TemplateAreabrickInterface extends AreabrickInterface
{
    const TEMPLATE_LOCATION_GLOBAL = 'global';

    const TEMPLATE_LOCATION_BUNDLE = 'bundle';

    const TEMPLATE_SUFFIX_TWIG = 'html.twig';

    /**
     * Determines if template should be auto-located in bundle or in project
     *
     */
    public function getTemplateLocation(): string;

    /**
     * Returns view suffix used to auto-build view names
     *
     */
    public function getTemplateSuffix(): string;
}
