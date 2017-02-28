<?php

namespace Pimcore\HttpKernel\Config;

use Pimcore\Config;
use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

class PimcoreConfigResource implements SelfCheckingResourceInterface, \Serializable
{
    /**
     * @var array
     */
    protected $parameters;

    public function __construct()
    {
        $this->parameters = $this->loadParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return serialize($this->getResource());
    }

    /**
     * @return array An array with two keys: 'prefix' for the prefix used and 'variables' containing all the variables watched by this resource
     */
    public function getResource()
    {
        return ['parameters' => $this->parameters];
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($timestamp)
    {
        return $this->loadParameters() === $this->parameters;
    }

    public function serialize()
    {
        return serialize($this->getResource());
    }

    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->parameters = $unserialized['parameters'];
    }

    /**
     * Load parameters from pimcore system config
     *
     * @return array
     */
    protected function loadParameters()
    {
        $config     = Config::getSystemConfig(true)->toArray();
        $parameters = $this->processConfig([], 'pimcore_config', $config);

        ksort($parameters);

        return $parameters;
    }

    /**
     * Iterate and flatten pimcore config
     *
     * @param array $parameters
     * @param $prefix
     * @param array $config
     *
     * @return array
     */
    protected function processConfig(array $parameters, $prefix, array $config)
    {
        foreach ($config as $key => $value) {
            $paramName = $prefix . '.' . $key;

            if (is_array($value)) {
                $parameters = $this->processConfig($parameters, $paramName, $value);
            } else {
                $parameters[$paramName] = $value;
            }
        }

        return $parameters;
    }
}
