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
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;
use Zend\View\Helper\AbstractHelper;
use Zend\View\Renderer\RendererInterface;

class GetParam extends AbstractHelper
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Action constructor.
     * @param RendererInterface $renderer
     * @param KernelInterface $kernel
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $action
     * @param $controller
     * @param $module
     * @param array $params
     * @return mixed
     */
    public function __invoke($name, $default = null)
    {
        return $this->container->get("request_stack")->getCurrentRequest()->get($name, $default);
    }

}
