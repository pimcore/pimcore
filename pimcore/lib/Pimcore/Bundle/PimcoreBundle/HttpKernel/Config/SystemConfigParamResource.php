<?php

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
        $container->addResource(new FileResource(Config::locateConfigFile('system.php')));
    }

    /**
     * Set pimcore config params on the container
     */
    public function setParameters()
    {
        $config = Config::getSystemConfig(true);
        $this->processConfig('pimcore_system_config', $config->toArray());
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

