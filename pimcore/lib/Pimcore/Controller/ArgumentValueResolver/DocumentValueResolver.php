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

use Pimcore\Model\Document as DocumentModel;
use Pimcore\Service\Request\DocumentResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Adds support for type hinting controller actions against `Document $document` and getting the current document.
 */
class DocumentValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @param DocumentResolver $documentResolver
     */
    public function __construct(DocumentResolver $documentResolver)
    {
        $this->documentResolver = $documentResolver;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        if ($argument->getType() !== DocumentModel::class) {
            return false;
        }

        if ($argument->getName() !== 'document') {
            return false;
        }

        $document = $this->documentResolver->getDocument($request);

        return $document && $document instanceof DocumentModel;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return \Generator|DocumentModel
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->documentResolver->getDocument($request);
    }
}
