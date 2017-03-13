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

namespace Pimcore\Bundle\PimcoreBundle\HttpKernel\Config;

use Pimcore\Config;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SystemConfigParamResource
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * @param ContainerBuilder $container
     */
    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;

        // register system.php as resource to rebuild container in dev on change
        $systemConfigFile = Config::locateConfigFile('system.php');
        if(file_exists($systemConfigFile)) {
            $container->addResource(new FileResource($systemConfigFile));
        }
    }

    /**
     * Set pimcore config params on the container
     */
    public function setParameters()
    {
        $config = Config::getSystemConfig(true);
        if($config) {
            $this->processConfig('pimcore_system_config', $config->toArray());
        } else {
            // default config which is necessary to initialize the container even if pimcore isn't installed
            // we only need parameters here which are referenced in the container to avoid compilation errors
            $this->processConfig('pimcore_system_config', [
                "database" => [
                    "params" => [
                        "host" => "localhost",
                        "port" => 3306,
                        "dbname" => "",
                        "username" => "root",
                        "password" => "",
                    ]
                ],
                "email" => [
                    "method" => "mail",
                    "smtp" => [
                        "host" => "",
                        "port" => "",
                        "ssl" => NULL,
                        "name" => "",
                        "auth" => [
                            "method" => NULL,
                            "username" => "",
                            "password" => ""
                        ]
                    ],
                    "debug" => [
                        "emailaddresses" => ""
                    ]
                ],
                "newsletter" => [
                    "method" => "mail",
                    "smtp" => [
                        "host" => "",
                        "port" => "",
                        "ssl" => NULL,
                        "name" => "",
                        "auth" => [
                            "method" => NULL,
                            "username" => "",
                            "password" => ""
                        ]
                    ]
                ]
            ]);
        }
    }

    /**
     * Iterate and flatten pimcore config and add it as parameters on the container
     *
     * @param string $prefix
     * @param array $config
     *
     * @return array
     */
    protected function processConfig($prefix, array $config)
    {
        foreach ($config as $key => $value) {
            $paramName = $prefix . '.' . $key;

            if (is_array($value)) {
                $this->processConfig($paramName, $value);
            } else {
                if (!$this->container->hasParameter($paramName)) {
                    $this->container->setParameter($paramName, $value);
                }
            }
        }
    }
}

