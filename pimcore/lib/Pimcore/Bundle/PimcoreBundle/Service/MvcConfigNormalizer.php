<?php

namespace Pimcore\Bundle\PimcoreBundle\Service;

use Doctrine\Common\Util\Inflector;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This service exists only as integration point between legacy module/controller/action <-> new bundle/controller/action
 * and template configuration and can be removed at a later point.
 */
class MvcConfigNormalizer
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var array
     */
    protected $bundleCache = [];

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Transform parent/controller/action into a controller reference string
     *
     * @param string|null $parent Bundle or (legacy) module
     * @param string|null $controller
     * @param string|null $action
     * @return string
     */
    public function formatController($parent = null, $controller = null, $action = null)
    {
        $result = $this->normalizeController($parent, $controller, $action);

        return sprintf(
            '%s:%s:%s',
            $result['bundle'],
            $result['controller'],
            $result['action']
        );
    }

    /**
     * Fallback helper to normalize module/controller/action names into Symfony notation. To be removed later.
     *
     * @param string|null $parent Bundle or (legacy) module
     * @param string|null $controller
     * @param string|null $action
     * @return array
     */
    public function normalizeController($parent = null, $controller = null, $action = null)
    {
        // TODO this is only temporary for backwards compatibility - remove when removing legacy support
        // TODO move constants to config
        $result = [
            'bundle'     => defined('PIMCORE_SYMFONY_DEFAULT_BUNDLE') ? PIMCORE_SYMFONY_DEFAULT_BUNDLE : 'AppBundle',
            'controller' => defined('PIMCORE_SYMFONY_DEFAULT_CONTROLLER') ? PIMCORE_SYMFONY_DEFAULT_CONTROLLER : 'Content',
            'action'     => defined('PIMCORE_SYMFONY_DEFAULT_ACTION') ? PIMCORE_SYMFONY_DEFAULT_ACTION : 'default'
        ];

        if ($parent) {
            $bundle = $this->normalizeBundle($parent);

            $result['bundle'] = $bundle;
        }

        if ($controller) {
            $controller           = ucfirst($controller);
            $result['controller'] = $controller;
        }

        if ($action) {
            $action           = Inflector::camelize($action);
            $result['action'] = $action;
        }

        return $result;
    }

    /**
     * Normalize bundle string into a valid bundle name
     *
     * @param string $bundle
     * @return string
     */
    public function normalizeBundle($bundle)
    {
        $originalBundle = $bundle;

        // bundle name contains Bundle - we assume it's properly formatted
        if (false !== strpos($bundle, 'Bundle')) {
            return $bundle;
        }

        $bundle = strtolower($bundle);
        if (isset($this->bundleCache[$bundle])) {
            return $this->bundleCache[$bundle];
        }

        foreach ($this->kernel->getBundles() as $bundleInstance) {
            if ($bundle . 'bundle' === strtolower($bundleInstance->getName())) {
                $this->bundleCache[$bundle] = $bundleInstance->getName();

                return $this->bundleCache[$bundle];
            }
        }

        throw new \RuntimeException(sprintf('Unable to normalize string %s into a valid bundle name', $originalBundle));
    }

    /**
     * Normalize template from .php to .html.php and remove leading slash
     *
     * @param string|null $template
     * @return string|null
     */
    public function normalizeTemplate($template = null)
    {
        if (empty($template)) {
            return $template;
        }

        // if we find Bundle in the template name we assume it's properly formatted
        if (false !== strpos($template, 'Bundle')) {
            return $template;
        }

        // replace .php with .html.php
        $suffixPattern = '/(?<!\.html)\.php$/i';
        if (preg_match($suffixPattern, $template)) {
            $template = preg_replace($suffixPattern, '.html.php', $template);
        }

        // split template into path and filename
        if (substr($template, 0, 1) === '/') {
            $template = substr($template, 1);
        }

        $path = '';
        if (false !== strpos($template, '/')) {
            $parts    = explode('/', $template);
            $template = array_pop($parts);

            // ucfirst to match views/Content - TODO should we remove this?
            $path = implode('/', $parts);
            $path = ucfirst($path);
        }

        // TODO move to config
        // TODO add support for non-bundled templates
        $bundle = defined('PIMCORE_SYMFONY_DEFAULT_BUNDLE') ? PIMCORE_SYMFONY_DEFAULT_BUNDLE : 'AppBundle';

        return sprintf(
            '%s:%s:%s',
            $bundle,
            $path,
            $template
        );
    }
}
