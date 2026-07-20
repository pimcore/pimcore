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

namespace Pimcore\Extension\Document\Areabrick;

/**
 * Base brick with template autoloading capabilities.
 *
 * Depending on the result of getTemplateLocation() and getTemplateSuffix() the tag handler builds the
 * following references:
 *
 * - @<bundle>/[A|a]reas/<brickId>/view.<suffix>
 *      -> resolves to <bundle>/templates/[A|a]reas/<brickId>/view.<suffix> (Symfony >= 5 structure)
 *         or <bundle>/Resources/views/[A|a]reas/<brickId>/view.<suffix> (Symfony <= 4 structure)
 * - areas/<brickId>/view.<suffix>
 *      -> resolves to <project>/templates/areas/<brickId>/view.<suffix>
 */
abstract class AbstractTemplateAreabrick extends AbstractAreabrick
{
    public function getTemplate(): ?string
    {
        // return null by default = auto-discover
        return null;
    }

    public function getTemplateLocation(): string
    {
        return static::TEMPLATE_LOCATION_GLOBAL;
    }

    public function getTemplateSuffix(): string
    {
        return static::TEMPLATE_SUFFIX_TWIG;
    }
}
