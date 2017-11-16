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

namespace Pimcore\Model\DataObject\ImportResolver;

use Pimcore\Localization\Locale;

class Code extends AbstractResolver
{
    /**
     * Id constructor.
     */
    public function __construct($config)
    {
        parent::__construct($config);

        $this->resolverImplementation = new $this->config->resolverSettings->phpClass($config);

        if (!$this->resolverImplementation) {
            throw new \Exception('could not resolve service: ' . $this->config->resolverSettings->service);
        }
    }

    /**
     * @param $parentId
     * @param $rowData
     *
     * @return static
     *
     * @throws \Exception
     */
    public function resolve($parentId, $rowData)
    {
        $container = \Pimcore::getContainer();
        $localeService = $container->get(Locale::class);
        $currentLocale = $localeService->getLocale();

        $locale = null;
        if ($this->config->resolverSettings) {
            if ($this->config->resolverSettings && $this->config->resolverSettings->language != 'default') {
                $localeService->setLocale($this->config->resolverSettings->language);
            }
        }

        $object = $this->resolverImplementation->resolve($parentId, $rowData);

        $localeService->setLocale($currentLocale);

        if (!$object) {
            throw new \Exception('Could not resolve object');
        }

        return $object;
    }
}
