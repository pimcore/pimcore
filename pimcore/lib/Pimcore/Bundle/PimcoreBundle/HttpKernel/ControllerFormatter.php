<?php

namespace Pimcore\Bundle\PimcoreBundle\HttpKernel;

use Doctrine\Common\Util\Inflector;

class ControllerFormatter
{
    /**
     * @var bool
     */
    protected $supportLegacy;

    /**
     * @param $supportLegacy
     */
    public function __construct($supportLegacy = true)
    {
        $this->supportLegacy = (bool)$supportLegacy;
    }

    /**
     * @param string $controller
     * @param string $action
     * @param string $parent
     * @return string
     */
    public function format($controller, $action, $parent = null)
    {
        if ($this->supportLegacy) {
            $result = $this->normalizeController($parent, $controller, $action);
        } else {
            if (null === $parent) {
                throw new \RuntimeException('Missing bundle');
            }

            $result = [
                'bundle'     => $parent,
                'controller' => $controller,
                'action'     => $action
            ];
        }

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
}
