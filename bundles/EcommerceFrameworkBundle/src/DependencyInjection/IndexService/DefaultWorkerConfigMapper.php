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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\IndexService;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultFindologic as DefaultFindologicConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysql as DefaultMysqlConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysqlSubTenantConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearch;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\OptimizedMysql as OptimizedMysqlConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultFindologic;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultMysql;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ElasticSearch\DefaultElasticSearch8;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\OptimizedMysql;

/**
 * Resolves default config or worker in case only config or worker is set
 *
 * @internal
 */
class DefaultWorkerConfigMapper
{
    /** @var array<string, string> */
    private array $mapping = [
        OptimizedMysqlConfig::class => OptimizedMysql::class,
        DefaultMysqlConfig::class => DefaultMysql::class,
        DefaultMysqlSubTenantConfig::class => DefaultMysql::class,
        ElasticSearch::class => DefaultElasticSearch8::class,
        DefaultFindologicConfig::class => DefaultFindologic::class,
    ];

    public function getWorkerForConfig(string $config): ?string
    {
        // we can only try to guess a worker if config is a class name we can resolve
        if (!class_exists($config)) {
            return null;
        }

        $reflector = new \ReflectionClass($config);
        foreach ($this->mapping as $configClass => $workerClass) {
            if ($reflector->getName() === $configClass || $reflector->isSubclassOf($configClass)) {
                return $workerClass;
            }
        }

        return null;
    }

    public function getConfigForWorker(string $worker): ?string
    {
        // we can only try to guess a config if worker is a class name we can resolve
        if (!class_exists($worker)) {
            return null;
        }

        $reflector = new \ReflectionClass($worker);
        foreach ($this->mapping as $configClass => $workerClass) {
            if ($reflector->getName() === $workerClass || $reflector->isSubclassOf($workerClass)) {
                return $configClass;
            }
        }

        return null;
    }
}
