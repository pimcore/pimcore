<?php

namespace Pimcore\Bundle\PimcoreBundle\Service;

use Doctrine\Common\Util\Inflector;

/**
 * This service exists only as integration point between legacy module/controller/action <-> new bundle/controller/action
 * and template configuration and can be removed at a later point.
 */
class MvcConfigNormalizer
{
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
            $bundle = $parent;
            if (strpos($parent, 'Bundle') === false) {
                $bundle = sprintf('%sBundle', Inflector::camelize($parent));
            }

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
     * Normalize template from .php to .phtml and remove leading slash
     *
     * @param string|null $template
     * @return string|null
     */
    public function normalizeTemplate($template = null)
    {
        if (empty($template)) {
            return $template;
        }

        $suffixPattern = '/\.php$/i';
        if (preg_match($suffixPattern, $template)) {
            $template = preg_replace($suffixPattern, '.phtml', $template);
        }

        if (substr($template, 0, 1) === '/') {
            $template = substr($template, 1);
        }

        return $template;
    }
}
