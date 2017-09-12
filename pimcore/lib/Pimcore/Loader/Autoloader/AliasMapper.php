<?php

declare(strict_types=1);

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

namespace Pimcore\Loader\Autoloader;

class AliasMapper extends AbstractAutoloader
{
    /**
     * Mapping from old to new class name
     *
     * @var array
     */
    private $mapping = [
        'Pimcore\Glossary\Processor' => \Pimcore\Tool\Glossary\Processor::class,
        'Pimcore\Admin\Helper\QueryParams' => \Pimcore\Bundle\AdminBundle\Helper\QueryParams::class,
        'Pimcore\Service\Locale' => \Pimcore\Localization\Locale::class,
        'Pimcore\Service\IntlFormatterService' => \Pimcore\Localization\IntlFormatter::class,
        'Pimcore\Service\WebPathResolver' => \Pimcore\HttpKernel\WebPathResolver::class,
        'Pimcore\Service\RequestMatcherFactory' => \Pimcore\Http\RequestMatcherFactory::class,
        'Pimcore\Service\Context\PimcoreContextGuesser' => \Pimcore\Http\Context\PimcoreContextGuesser::class,
        'Pimcore\Service\Request\PimcoreContextResolverAwareInterface' => \Pimcore\Http\Context\PimcoreContextResolverAwareInterface::class,
        'Pimcore\Service\Request\AbstractRequestResolver' => \Pimcore\Http\Request\Resolver\AbstractRequestResolver::class,
        'Pimcore\Service\Request\DocumentResolver' => \Pimcore\Http\Request\Resolver\DocumentResolver::class,
        'Pimcore\Service\Request\EditmodeResolver' => \Pimcore\Http\Request\Resolver\EditmodeResolver::class,
        'Pimcore\Service\Request\PimcoreContextResolver' => \Pimcore\Http\Request\Resolver\PimcoreContextResolver::class,
        'Pimcore\Service\Request\ResponseHeaderResolver' => \Pimcore\Http\Request\Resolver\ResponseHeaderResolver::class,
        'Pimcore\Service\Request\SiteResolver' => \Pimcore\Http\Request\Resolver\SiteResolver::class,
        'Pimcore\Service\Request\TemplateResolver' => \Pimcore\Http\Request\Resolver\TemplateResolver::class,
        'Pimcore\Service\Request\TemplateVarsResolver' => \Pimcore\Http\Request\Resolver\TemplateVarsResolver::class,
        'Pimcore\Service\Request\ViewModelResolver' => \Pimcore\Http\Request\Resolver\ViewModelResolver::class,
    ];

    public function createAliases()
    {
        foreach ($this->mapping as $oldName => $newName) {
            if (!$this->classExists($oldName, false)) {
                class_alias($newName, $oldName);
            }
        }
    }
}
