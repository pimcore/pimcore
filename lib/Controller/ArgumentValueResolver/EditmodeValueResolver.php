<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Controller\ArgumentValueResolver;

use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @internal
 */
final class EditmodeValueResolver implements ValueResolverInterface
{
    private EditmodeResolver $editmodeResolver;

    public function __construct(EditmodeResolver $editmodeResolver)
    {
        $this->editmodeResolver = $editmodeResolver;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== 'bool') {
            return [];
        }

        if ($argument->getName() !== 'editmode') {
            return [];
        }

        return [$this->editmodeResolver->isEditmode($request)];
    }
}
