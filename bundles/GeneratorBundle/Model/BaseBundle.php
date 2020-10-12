<?php

namespace Pimcore\Bundle\GeneratorBundle\Model;

use Symfony\Component\DependencyInjection\Container;

/**
 * @deprecated
 * Represents a bundle being built.
 *
 * The following class is copied from \Sensio\Bundle\GeneratorBundle\Model\Bundle
 */
class BaseBundle
{
    private $namespace;

    private $name;

    private $targetDirectory;

    private $configurationFormat;

    private $isShared;

    private $testsDirectory;

    public function __construct($namespace, $name, $targetDirectory, $configurationFormat, $isShared)
    {
        $this->namespace = $namespace;
        $this->name = $name;
        $this->targetDirectory = $targetDirectory;
        $this->configurationFormat = $configurationFormat;
        $this->isShared = $isShared;
        $this->testsDirectory = $this->getTargetDirectory().'/Tests';
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getConfigurationFormat()
    {
        return $this->configurationFormat;
    }

    public function isShared()
    {
        return $this->isShared;
    }

    /**
     * Returns the directory where the bundle will be generated.
     *
     * @return string
     */
    public function getTargetDirectory()
    {
        return rtrim($this->targetDirectory, '/').'/'.trim(strtr($this->namespace, '\\', '/'), '/');
    }

    /**
     * Returns the name of the bundle without the Bundle suffix.
     *
     * @return string
     */
    public function getBasename()
    {
        return substr($this->name, 0, -6);
    }

    /**
     * Returns the dependency injection extension alias for this bundle.
     *
     * @return string
     */
    public function getExtensionAlias()
    {
        return Container::underscore($this->getBasename());
    }

    /**
     * Should a DependencyInjection directory be generated for this bundle?
     *
     * @return bool
     */
    public function shouldGenerateDependencyInjectionDirectory()
    {
        return $this->isShared;
    }

    /**
     * What is the filename for the services.yml/xml file?
     *
     * @return string
     */
    public function getServicesConfigurationFilename()
    {
        if ('yml' === $this->getConfigurationFormat() || 'annotation' === $this->configurationFormat) {
            return 'services.yml';
        } else {
            return 'services.'.$this->getConfigurationFormat();
        }
    }

    /**
     * What is the filename for the routing.yml/xml file?
     *
     * If false, no routing file will be generated
     *
     * @return string|bool
     */
    public function getRoutingConfigurationFilename()
    {
        if ($this->getConfigurationFormat() == 'annotation') {
            return false;
        }

        return 'routing.'.$this->getConfigurationFormat();
    }

    /**
     * Returns the class name of the Bundle class.
     *
     * @return string
     */
    public function getBundleClassName()
    {
        return $this->namespace.'\\'.$this->name;
    }

    public function setTestsDirectory($testsDirectory)
    {
        $this->testsDirectory = $testsDirectory;
    }

    public function getTestsDirectory()
    {
        return $this->testsDirectory;
    }
}
