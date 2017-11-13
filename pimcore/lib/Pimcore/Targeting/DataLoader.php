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

namespace Pimcore\Targeting;

use Pimcore\Targeting\Model\VisitorInfo;

class DataLoader implements DataLoaderInterface
{
    /**
     * @var DataProviderLocatorInterface
     */
    private $dataProviders;

    public function __construct(DataProviderLocatorInterface $dataProviders)
    {
        $this->dataProviders = $dataProviders;
    }

    /**
     * @inheritdoc
     */
    public function loadDataFromProviders(VisitorInfo $visitorInfo, $providerKeys)
    {
        if (!is_array($providerKeys)) {
            $providerKeys = [(string)$providerKeys];
        }

        foreach ($providerKeys as $providerKey) {
            $loadedProviders = $visitorInfo->get('_data_providers', []);

            // skip already loaded providers to avoid circular reference loops
            if (in_array($providerKey, $loadedProviders)) {
                continue;
            }

            $loadedProviders[] = $providerKey;
            $visitorInfo->set('_data_providers', $loadedProviders);

            $dataProvider = $this->dataProviders->get($providerKey);

            // load data from required providers
            if ($dataProvider instanceof DataProviderDependentInterface) {
                $this->loadDataFromProviders(
                    $visitorInfo,
                    $dataProvider->getDataProviderKeys()
                );
            }

            $dataProvider->load($visitorInfo);
        }
    }
}
