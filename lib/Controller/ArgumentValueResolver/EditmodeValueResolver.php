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

use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class EditmodeValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var EditmodeResolver
     */
    private $editmodeResolver;

    public function __construct(EditmodeResolver $editmodeResolver)
    {
        $this->editmodeResolver = $editmodeResolver;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getType() === 'bool' && $argument->getName() === 'editmode';
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->editmodeResolver->isEditmode($request);
    }
}
