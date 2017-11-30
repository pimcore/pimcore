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

use Pimcore\Debug\Traits\StopwatchTrait;
use Pimcore\Targeting\DataProvider\DataProviderInterface;
use Pimcore\Targeting\Model\VisitorInfo;
use Psr\Container\ContainerInterface;

class DataLoader implements DataLoaderInterface
{
    use StopwatchTrait;

    /**
     * @var ContainerInterface
     */
    private $dataProviders;

    public function __construct(ContainerInterface $dataProviders)
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

            $this->startStopwatch('Targeting:load:' . $providerKey, 'targeting');

            $dataProvider->load($visitorInfo);

            $this->stopStopwatch('Targeting:load:' . $providerKey);
        }
    }

    /**
     * @inheritdoc
     */
    public function hasDataProvider(string $type): bool
    {
        return $this->dataProviders->has($type);
    }

    /**
     * @inheritdoc
     */
    public function getDataProvider(string $type): DataProviderInterface
    {
        if (!$this->dataProviders->has($type)) {
            throw new \InvalidArgumentException(sprintf(
                'There is no data provider registered for type "%s"',
                $type
            ));
        }

        return $this->dataProviders->get($type);
    }
}
