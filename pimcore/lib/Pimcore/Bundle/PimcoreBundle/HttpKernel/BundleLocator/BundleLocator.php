<?php

namespace Pimcore\Bundle\PimcoreBundle\HttpKernel\BundleLocator;

use Symfony\Component\HttpKernel\KernelInterface;

class BundleLocator implements BundleLocatorInterface
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var array
     */
    protected $bundlePathCache = [];

    /**
     * @var array
     */
    protected $classPathCache = [];

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function getBundle($className)
    {
        // TODO there's a simpler method in TemplateGuesser - check we can use that

        $classBundlePath = $this->resolveBundlePath($className);

        if (isset($this->bundlePathCache[$classBundlePath])) {
            return $this->bundlePathCache[$classBundlePath];
        }

        foreach ($this->kernel->getBundles() as $bundle) {
            if (strpos($bundle->getPath(), $classBundlePath) !== false) {
                $bundlePath = $this->sanitizePath($bundle->getPath());

                if ($bundlePath === $classBundlePath) {
                    $this->bundlePathCache[$classBundlePath] = $bundle;

                    return $bundle;
                }
            }
        }

        throw new NotFoundException(sprintf('Unable to find bundle for class %s', is_object($className) ? get_class($className) : $className));
    }

    /**
     * {@inheritdoc}
     */
    public function resolveBundlePath($className)
    {
        $cacheKey = is_object($className) ? get_class($className) : $className;

        if (isset($this->classPathCache[$cacheKey])) {
            return $this->classPathCache[$cacheKey];
        }

        if (!is_object($className)) {
            if (!class_exists($className)) {
                throw new InvalidArgumentException(sprintf('Class name %s does not exist', $className));
            }
        }

        $reflector = new \ReflectionClass($className);

        $classDir      = $this->sanitizePath(dirname($reflector->getFileName()));
        $classDirParts = explode(DIRECTORY_SEPARATOR, $classDir);

        $matched        = false;
        $bundleDirParts = [];

        // walk through parts until we find *Bundle
        while ($part = array_pop($classDirParts)) {
            if (!$matched && preg_match('/^([a-zA-Z]+Bundle)$/', $part)) {
                $matched = true;
            }

            if ($matched) {
                $bundleDirParts[] = $part;
            }
        }

        if (count($bundleDirParts) === 0) {
            throw new NotFoundException(sprintf('Unable to extract bundle path from class %s', $reflector->getName()));
        }

        $bundleDirParts = array_reverse($bundleDirParts);
        $bundleDir      = implode(DIRECTORY_SEPARATOR, $bundleDirParts);

        $this->classPathCache[$cacheKey] = $bundleDir;

        return $bundleDir;
    }

    /**
     * @param string $path
     * @return string
     */
    protected function sanitizePath($path)
    {
        $root = realpath($this->kernel->getRootDir() . '/..');

        $sanitizedPath = str_replace($root, '', $path);
        $sanitizedPath = trim($sanitizedPath, DIRECTORY_SEPARATOR);

        return $sanitizedPath;
    }
}
