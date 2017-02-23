<?php

namespace PimcoreLegacyBundle\HttpKernel;

use Pimcore\Cache;
use Pimcore\Config;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Kernel implements KernelInterface
{
    /**
     * @var KernelInterface
     */
    protected $mainKernel;

    /**
     * @var int
     */
    protected $startTime;

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * @param KernelInterface $mainKernel
     */
    public function __construct(KernelInterface $mainKernel)
    {
        $this->mainKernel = $mainKernel;

        if ($this->isDebug()) {
            $this->startTime = microtime(true);
        }
    }

    /**
     * Gets the name of the kernel.
     *
     * @return string The kernel name
     */
    public function getName()
    {
        return 'legacy';
    }

    /**
     * Boots the current kernel.
     */
    public function boot()
    {
        if (!$this->booted) {

            $this->setupTempDirectories();

            // initialize cache
            Cache::init();

            $this->initializePlugins();

            // prepare the ZF MVC stack - needed for more advanced view helpers like action()
            \Pimcore\Legacy::prepareMvc(true);

            // set a default request
            $front = \Zend_Controller_Front::getInstance();
            $front->setRequest(new \Zend_Controller_Request_Http());
            $front->setResponse(new \Zend_Controller_Response_Http());

            $this->booted = true;
        }
    }

    /**
     * Initialize legacy plugins
     */
    protected function initializePlugins()
    {
        \Pimcore\Legacy::initPlugins(); // TODO move somewhere else?
    }

    /**
     * Try to set tmp directoy into superglobals, ZF and other frameworks (PEAR) sometimes relies on that
     */
    public function setupTempDirectories()
    {
        foreach (['TMPDIR', 'TEMP', 'TMP', 'windir', 'SystemRoot'] as $key) {
            $_ENV[$key] = PIMCORE_CACHE_DIRECTORY;
            $_SERVER[$key] = PIMCORE_CACHE_DIRECTORY;
        }
    }

    /**
     * Handles a Request to convert it to a Response.
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to convert them to a Response instance.
     *
     * @param Request $request A Request instance
     * @param int $type The type of the request
     *                         (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     * @param bool $catch Whether to catch exceptions or not
     *
     * @return Response A Response instance
     *
     * @throws \Exception When an Exception occurs during processing
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        throw new \RuntimeException('handle() is not yet implemented (see FallbackController for now)');
    }

    /**
     * @param \Zend_Controller_Request_Http|null $zendRequest
     * @return \Zend_Controller_Response_Http
     */
    public function run(\Zend_Controller_Request_Http $zendRequest = null)
    {
        $this->boot();

        return \Pimcore\Legacy::run(true, $zendRequest);
    }

    /**
     * Returns an array of bundles to register.
     *
     * @return BundleInterface[] An array of bundle instances
     */
    public function registerBundles()
    {
        return [];
    }

    /**
     * Loads the container configuration.
     *
     * @param LoaderInterface $loader A LoaderInterface instance
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }

    /**
     * Shutdowns the kernel.
     *
     * This method is mainly useful when doing functional testing.
     */
    public function shutdown()
    {
    }

    /**
     * Gets the registered bundle instances.
     *
     * @return BundleInterface[] An array of registered bundle instances
     */
    public function getBundles()
    {
        return [];
    }

    /**
     * Returns a bundle and optionally its descendants by its name.
     *
     * @param string $name Bundle name
     * @param bool $first Whether to return the first bundle only or together with its descendants
     *
     * @return BundleInterface|BundleInterface[] A BundleInterface instance or an array of BundleInterface instances if $first is false
     *
     * @throws \InvalidArgumentException when the bundle is not enabled
     */
    public function getBundle($name, $first = true)
    {
        throw new \InvalidArgumentException('The legacy kernel does not support bundles');
    }

    /**
     * Returns the file path for a given resource.
     *
     * A Resource can be a file or a directory.
     *
     * The resource name must follow the following pattern:
     *
     *     "@BundleName/path/to/a/file.something"
     *
     * where BundleName is the name of the bundle
     * and the remaining part is the relative path in the bundle.
     *
     * If $dir is passed, and the first segment of the path is "Resources",
     * this method will look for a file named:
     *
     *     $dir/<BundleName>/path/without/Resources
     *
     * before looking in the bundle resource folder.
     *
     * @param string $name A resource name to locate
     * @param string $dir A directory where to look for the resource first
     * @param bool $first Whether to return the first path or paths for all matching bundles
     *
     * @return string|array The absolute path of the resource or an array if $first is false
     *
     * @throws \InvalidArgumentException if the file cannot be found or the name is not valid
     * @throws \RuntimeException         if the name contains invalid/unsafe characters
     */
    public function locateResource($name, $dir = null, $first = true)
    {
        throw new \InvalidArgumentException('The legacy kernel does not support resource loading');
    }

    /**
     * Gets the environment.
     *
     * @return string The current environment
     */
    public function getEnvironment()
    {
        return Config::getEnvironment();
    }

    /**
     * Checks if debug mode is enabled.
     *
     * @return bool true if debug mode is enabled, false otherwise
     */
    public function isDebug()
    {
        return $this->mainKernel->isDebug();
    }

    /**
     * Gets the current container.
     *
     * @return ContainerInterface A ContainerInterface instance
     */
    public function getContainer()
    {
        return $this->mainKernel->getContainer();
    }

    /**
     * Gets the request start time (not available if debug is disabled).
     *
     * @return int The request start timestamp
     */
    public function getStartTime()
    {
        return $this->isDebug() ? $this->startTime : -INF;
    }

    /**
     * Gets the application root dir.
     *
     * @return string The application root dir
     */
    public function getRootDir()
    {
        return $this->mainKernel->getRootDir();
    }

    /**
     * Gets the cache directory.
     *
     * @return string The cache directory
     */
    public function getCacheDir()
    {
        return $this->mainKernel->getCacheDir();
    }

    /**
     * Gets the log directory.
     *
     * @return string The log directory
     */
    public function getLogDir()
    {
        return $this->mainKernel->getLogDir();
    }

    /**
     * Gets the charset of the application.
     *
     * @return string The charset
     */
    public function getCharset()
    {
        return $this->mainKernel->getCharset();
    }

    public function serialize()
    {
        return serialize([]);
    }

    public function unserialize($data)
    {
        $this->__construct(\Pimcore::getKernel());
    }
}
