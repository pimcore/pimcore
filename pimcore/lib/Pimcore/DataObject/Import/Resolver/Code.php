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
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\Import\Resolver;

use Pimcore\Localization\Locale;

class Code extends AbstractResolver
{
    /**
     * @var Locale
     */
    private $localeService;

    public function __construct(Locale $localeService)
    {
        $this->localeService = $localeService;
    }

    public function resolve(\stdClass $config, int $parentId, array $rowData)
    {
        /** @var ResolverInterface $resolverImplementation */
        $resolverImplementation = new $config->resolverSettings->phpClass();

        if (!$resolverImplementation) {
            throw new \Exception('could not resolve service: ' . $config->resolverSettings->service);
        }

        $currentLocale = $this->localeService->getLocale();

        $locale = null;
        if ($config->resolverSettings) {
            if ($config->resolverSettings && 'default' !== $config->resolverSettings->language) {
                $this->localeService->setLocale($config->resolverSettings->language);
            }
        }

        $object = $resolverImplementation->resolve($config, $parentId, $rowData);

        $this->localeService->setLocale($currentLocale);

        if (!$object) {
            throw new \Exception('Could not resolve object');
        }

        return $object;
    }
}
