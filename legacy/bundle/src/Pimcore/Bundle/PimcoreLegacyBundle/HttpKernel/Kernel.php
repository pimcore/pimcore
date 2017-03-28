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

namespace Pimcore\Bundle\PimcoreLegacyBundle\HttpKernel;

use Pimcore\Config;
use Pimcore\Legacy;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
            $this->initializePlugins();

            // prepare the ZF MVC stack - needed for more advanced view helpers like action()
            Legacy::prepareMvc(true);

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
        $this->getContainer()->get('pimcore.legacy.plugin_broker')->initPlugins();
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
     * @inheritdoc
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        $this->boot();

        $zendResponse = null;

        try {
            $zendRequest  = new \Zend_Controller_Request_Http();
            $zendResponse = Legacy::run(true, $zendRequest);
        } catch (\Zend_Controller_Router_Exception $e) {
            if ($e->getMessage()) {
                throw new NotFoundHttpException($e->getMessage(), $e);
            } else {
                throw new NotFoundHttpException('Not Found', $e);
            }
        }

        $response = $this->transformZendResponse($zendResponse);

        return $response;
    }

    /**
     * @param \Zend_Controller_Response_Http $zendResponse
     *
     * @return Response
     */
    public function transformZendResponse(\Zend_Controller_Response_Http $zendResponse)
    {
        $response = new Response($zendResponse->getBody(), $zendResponse->getHttpResponseCode());
        foreach ($zendResponse->getHeaders() as $header) {
            $response->headers->set($header['name'], $header['value']);
        }

        return $response;
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
