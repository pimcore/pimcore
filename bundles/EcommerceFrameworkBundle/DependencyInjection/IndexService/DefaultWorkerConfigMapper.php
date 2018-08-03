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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\IndexService;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultFactFinder as DefaultFactFinderConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultFindologic as DefaultFindologicConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysql as DefaultMysqlConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysqlSubTenantConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearch;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\OptimizedMysql as OptimizedMysqlConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultElasticSearch;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultFactFinder;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultFindologic;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultMysql;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\OptimizedMysql;

/**
 * Resolves default config or worker in case only config or worker is set
 */
class DefaultWorkerConfigMapper
{
    private $mapping = [
        OptimizedMysqlConfig::class        => OptimizedMysql::class,
        DefaultMysqlConfig::class          => DefaultMysql::class,
        DefaultMysqlSubTenantConfig::class => DefaultMysql::class,
        ElasticSearch::class               => DefaultElasticSearch::class,
        DefaultFactFinderConfig::class     => DefaultFactFinder::class,
        DefaultFindologicConfig::class     => DefaultFindologic::class,
    ];

    public function getWorkerForConfig(string $config)
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
    }

    public function getConfigForWorker(string $worker)
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
    }
}
