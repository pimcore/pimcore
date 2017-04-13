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

use Pimcore\Service\Request\TemplateVarsResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class TemplateVarsValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var TemplateVarsResolver
     */
    protected $templateVarsResolver;

    /**
     * @param TemplateVarsResolver $templateVarsResolver
     */
    public function __construct(TemplateVarsResolver $templateVarsResolver)
    {
        $this->templateVarsResolver = $templateVarsResolver;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return $argument->getType() === 'array' && $argument->getName() === 'templateVars';
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return \Generator|array
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->templateVarsResolver->getTemplateVars($request);
    }
}
