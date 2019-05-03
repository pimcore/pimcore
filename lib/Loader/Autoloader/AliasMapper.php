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
        'Pimcore\Service\Locale' => \Pimcore\Localization\LocaleService::class,
        'Pimcore\Localization\Locale' => \Pimcore\Localization\LocaleService::class,
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
        'Pimcore\Model\DataObject\ClassDefinition\Data\Multihref' => \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation::class,
        'Pimcore\Model\DataObject\ClassDefinition\Data\Href' => \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation::class,
        'Pimcore\Model\DataObject\ClassDefinition\Data\MultihrefMetadata' => \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation::class,
        'Pimcore\Model\DataObject\ClassDefinition\Data\Objects' => \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation::class,
        'Pimcore\Model\DataObject\ClassDefinition\Data\ObjectsMetadata' => \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation::class,
        'Pimcore\Model\DataObject\ClassDefinition\Data\Nonownerobjects' => \Pimcore\Model\DataObject\ClassDefinition\Data\ReverseManyToManyObjectRelation::class,        'Pimcore\Model\DataObject\ClassDefinition\Data\Multihref' => \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation::class,
        'Pimcore\Model\Object\ClassDefinition\Data\Multihref' => \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation::class,
        'Pimcore\Model\Object\ClassDefinition\Data\Href' => \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation::class,
        'Pimcore\Model\Object\ClassDefinition\Data\MultihrefMetadata' => \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation::class,
        'Pimcore\Model\Object\ClassDefinition\Data\Objects' => \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation::class,
        'Pimcore\Model\Object\ClassDefinition\Data\ObjectsMetadata' => \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation::class,
        'Pimcore\Model\Object\ClassDefinition\Data\Nonownerobjects' => \Pimcore\Model\DataObject\ClassDefinition\Data\ReverseManyToManyObjectRelation::class,
        'Pimcore\Model\Document\Tag\Href' => \Pimcore\Model\Document\Tag\Relation::class,
        'Pimcore\Model\Document\Tag\Multihref' => \Pimcore\Model\Document\Tag\Relations::class,
    ];

    public function load(string $class)
    {
        // alias was requested, load original and create alias
        if (isset($this->mapping[$class])) {
            if (!$this->classExists($this->mapping[$class], false)) {
                $this->composerAutoloader->loadClass($this->mapping[$class]);
            }

            if (!$this->classExists($class, false) && $this->classExists($this->mapping[$class], false)) {
                class_alias($this->mapping[$class], $class);
            }
        }

        // original was requested, load it and create alias afterwards
        $aliases = array_keys($this->mapping, $class);
        if (count($aliases)) {
            $this->composerAutoloader->loadClass($class);
            // the return of composer autoloader obviously doesn't work, be better check manually if the class really exists
            if ($this->classExists($class, false)) {
                foreach ($aliases as $alias) {
                    class_alias($class, $alias);
                }
            }
        }
    }
}
