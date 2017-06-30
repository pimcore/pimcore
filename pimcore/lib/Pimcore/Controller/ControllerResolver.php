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

namespace Pimcore\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class ControllerResolver implements ControllerResolverInterface
{
    /**
     * @var ControllerResolverInterface
     */
    private $resolver;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ControllerResolverInterface $resolver
     */
    public function __construct(ControllerResolverInterface $resolver, ContainerInterface $container)
    {
        $this->resolver = $resolver;
        $this->container = $container;
    }

    /**
     * Fixes https://github.com/symfony/symfony/issues/23200 until fixed in Symfony (controllers defined as
     * services do not have a container set despite implementing ContainerAwareInterface).
     *
     * Can be removed as soon as the issue is fixed.
     *
     * @inheritDoc
     */
    public function getController(Request $request)
    {
        $controller = $request->attributes->get('_controller');
        $resolvedController = $this->resolver->getController($request);

        if (1 === substr_count($controller, ':') && is_array($resolvedController)) {
            if ($resolvedController[0] instanceof ContainerAwareInterface) {
                $resolvedController[0]->setContainer($this->container);
            }

            if ($resolvedController[0] instanceof AbstractController && null !== $previousContainer = $resolvedController[0]->setContainer($this->container)) {
                $resolvedController[0]->setContainer($previousContainer);
            }
        }

        return $resolvedController;
    }

    /**
     * @inheritDoc
     */
    public function getArguments(Request $request, $controller)
    {
        return $this->resolver->getArguments($request, $controller);
    }
}
