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

namespace Pimcore\Controller\ArgumentValueResolver;

use Pimcore\Http\Request\Resolver\ViewModelResolver;
use Pimcore\Templating\Model\ViewModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @deprecated
 */
class ViewModelValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var ViewModelResolver
     */
    protected $viewModelResolver;

    /**
     * @param ViewModelResolver $viewModelResolver
     */
    public function __construct(ViewModelResolver $viewModelResolver)
    {
        $this->viewModelResolver = $viewModelResolver;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return $argument->getType() === ViewModel::class && $argument->getName() === 'view';
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return \Generator|ViewModel
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->viewModelResolver->getViewModel($request);
    }
}
