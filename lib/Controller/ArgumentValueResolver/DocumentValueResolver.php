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

use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Adds support for type hinting controller actions against `Document $document` and getting the current document.
 *
 * @internal
 */
final class DocumentValueResolver implements ArgumentValueResolverInterface
{
    protected DocumentResolver $documentResolver;

    public function __construct(DocumentResolver $documentResolver)
    {
        $this->documentResolver = $documentResolver;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->getType() !== Document::class) {
            return false;
        }

        if ($argument->getName() !== 'document') {
            return false;
        }

        $document = $this->documentResolver->getDocument($request);

        return $document && $document instanceof Document;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $this->documentResolver->getDocument($request);
    }
}
