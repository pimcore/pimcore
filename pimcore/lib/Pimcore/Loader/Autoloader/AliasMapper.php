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
