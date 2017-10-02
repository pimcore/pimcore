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

namespace Pimcore\Controller\Config;

use Doctrine\Common\Util\Inflector;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This service exists as integration point between legacy module/controller/action <-> new bundle/controller/action and
 * to handle default bundle/controller/action in case it is not configured.
 *
 * Most of the normalizations here could be removed when we do not need to support legacy ZF1 notations (e.g. DB was
 * fully migrated).
 *
 * TODO use a config switch to enable/disable ZF1 compatibility
 */
class ConfigNormalizer
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var array
     */
    private $bundleCache = [];

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
     * @param string|null $bundle Bundle or (legacy) module
     * @param string|null $controller
     * @param string|null $action
     *
     * @return string
     */
    public function formatControllerReference(string $bundle = null, string $controller = null, string $action = null): string
    {
        $action = $this->normalizeActionName($action);

        // check if controller is a service (prefixed with @)
        if (null !== $controller && 0 === strpos($controller, '@')) {
            return sprintf(
                '%s:%sAction',
                substr($controller, 1),
                $action
            );
        } else {
            $bundle = $this->normalizeBundleName($bundle);
            $controller = $this->normalizeControllerName($controller);

            return sprintf(
                '%s:%s:%s',
                $bundle,
                $controller,
                $action
            );
        }
    }

    /**
     * Normalize module/bundle name (App -> AppBundle, module -> ModuleBundle)
     *
     * @param string|null $bundle
     *
     * @return string
     */
    public function normalizeBundleName(string $bundle = null): string
    {
        if (empty($bundle)) {
            return defined('PIMCORE_SYMFONY_DEFAULT_BUNDLE') ? PIMCORE_SYMFONY_DEFAULT_BUNDLE : 'AppBundle';
        }

        $originalBundle = $bundle;

        // bundle name contains Bundle - we assume it's properly formatted
        if (false !== strpos($bundle, 'Bundle')) {
            return $bundle;
        }

        // App -> AppBundle
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

        throw new \RuntimeException(sprintf('Unable to normalize string "%s" into a valid bundle name', $originalBundle));
    }

    /**
     * Normalize controller name from category_controller to Category/Controller
     *
     * @param string|null $controller
     *
     * @return string
     */
    public function normalizeControllerName(string $controller = null): string
    {
        if (empty($controller)) {
            return defined('PIMCORE_SYMFONY_DEFAULT_BUNDLE') ? PIMCORE_SYMFONY_DEFAULT_BUNDLE : 'Content';
        }

        // split submodules with _ and uppercase first character
        $controllerParts = array_map(function ($part) {
            return ucfirst($part);
        }, explode('_', $controller));

        $controller = implode('/', $controllerParts);

        return $controller;
    }

    /**
     * Normalize action name form action-name to actionName
     *
     * @param string|null $action
     *
     * @return string
     */
    public function normalizeActionName(string $action = null): string
    {
        if (empty($action)) {
            return defined('PIMCORE_SYMFONY_DEFAULT_ACTION') ? PIMCORE_SYMFONY_DEFAULT_ACTION : 'default';
        }

        return Inflector::camelize($action);
    }

    /**
     * Normalize template from .php to .html.php and remove leading slash
     *
     * @param string|null $template
     *
     * @return string|null
     */
    public function normalizeTemplateName(string $template = null)
    {
        // we just return the original value as template could be null
        // do NOT use a string return type for this method!
        if (empty($template)) {
            return $template;
        }

        // if we find colons in the template name we assume it's properly formatted for Symfony
        if (false !== strpos($template, ':')) {
            return $template;
        }

        // if we find Bundle in the template name we assume it's properly formatted for Symfony
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
            $parts = explode('/', $template);
            $template = array_pop($parts);

            // ucfirst to match views/Content - TODO should we remove this?
            $path = implode('/', $parts);
            $path = ucfirst($path);
        }

        return sprintf('%s/%s', $path, $template);
    }
}
