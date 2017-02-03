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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend\Helper;

use Symfony\Bundle\FrameworkBundle\Templating\Helper\ActionsHelper;
use Symfony\Component\HttpKernel\KernelInterface;
use Zend\View\Helper\AbstractHelper;
use Zend\View\Renderer\RendererInterface;

class Action extends AbstractHelper
{
    /**
     * @var ActionsHelper
     */
    protected $actionsHelper;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var array
     */
    private $moduleCache = [];

    /**
     * Action constructor.
     * @param RendererInterface $renderer
     * @param KernelInterface $kernel
     */
    public function __construct(ActionsHelper $actionsHelper, KernelInterface $kernel)
    {
        $this->actionsHelper = $actionsHelper;
        $this->kernel = $kernel;
    }

    /**
     * @param $action
     * @param $controller
     * @param $module
     * @param array $params
     * @return mixed
     */
    public function __invoke($action, $controller, $module, array $params = array())
    {

        $options['attributes'] = $params;
        $symfonyController = sprintf('%sBundle:%s:%s',
            $this->formatModule($module), $this->formatController($controller), $action
        );

        // TODO check if this is the best way to do  that
        return $this->actionsHelper->render(
            new \Symfony\Component\HttpKernel\Controller\ControllerReference($symfonyController, $params)
        );

    }

    /**
     * Get correct casing of the module which is based on the bundle.
     *
     * @param  string $module
     * @return string
     */
    protected function formatModule($module)
    {
        $module = strtolower($module);
        if (isset($this->moduleCache[$module])) {
            return $this->moduleCache[$module];
        }
        foreach ($this->kernel->getBundles() AS $bundle) {
            if ($module."bundle" == strtolower($bundle->getName())) {
                return $this->moduleCache[$module] = str_replace("Bundle", "", $bundle->getName());
            }
        }
        throw new \RuntimeException("Couldnt find a matching bundle for the module $module");
    }

    protected function formatController($controller)
    {
        return ucfirst($controller);
    }


}
