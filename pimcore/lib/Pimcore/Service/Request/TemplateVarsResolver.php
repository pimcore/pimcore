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

namespace Pimcore\Service\Request;

use Symfony\Component\HttpFoundation\Request;

class TemplateVarsResolver
{
    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @param DocumentResolver $documentResolver
     * @param EditmodeResolver $editmodeResolver
     */
    public function __construct(DocumentResolver $documentResolver, EditmodeResolver $editmodeResolver)
    {
        $this->documentResolver = $documentResolver;
        $this->editmodeResolver = $editmodeResolver;
    }

    /**
     * @param Request|null $request
     * @return array
     */
    public function getTemplateVars(Request $request = null)
    {
        return [
            'document' => $this->documentResolver->getDocument($request),
            'editmode' => $this->editmodeResolver->isEditmode($request)
        ];
    }
}
