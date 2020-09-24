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

namespace Pimcore\Extension\Document\Areabrick;

/**
 * Base brick with template autoloading capabilities.
 *
 * Depending on the result of getTemplateLocation and getTemplateSuffix the tag handler builds the following references:
 *
 * - <currentBundle>:Areas/<brickId>/(view|edit).<suffix>
 * - Areas/<brickId>/(view|edit).<suffix> -> resolves to app/Resources
 */
abstract class AbstractTemplateAreabrick extends AbstractAreabrick implements TemplateAreabrickInterface
{
    /**
     * @inheritDoc
     */
    public function getTemplate()
    {
        // return null by default = auto-discover
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getViewTemplate()
    {
        @trigger_error(sprintf('%s is deprecated, use getTemplate() instead', __METHOD__), E_USER_DEPRECATED);

        return $this->getTemplate();
    }

    /**
     * @inheritDoc
     */
    public function getTemplateLocation()
    {
        return static::TEMPLATE_LOCATION_BUNDLE;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateSuffix()
    {
        return static::TEMPLATE_SUFFIX_PHP;
    }
}
