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

namespace Pimcore\Bundle\CoreBundle\DataCollector;

use Pimcore\Service\Request\PimcoreContextResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class PimcoreDataCollector extends DataCollector
{
    /**
     * @var PimcoreContextResolver
     */
    protected $contextResolver;

    /**
     * @param PimcoreContextResolver $contextResolver
     */
    public function __construct(PimcoreContextResolver $contextResolver)
    {
        $this->contextResolver = $contextResolver;
    }

    /**
     * @inheritDoc
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'context' => $this->contextResolver->getPimcoreContext($request)
        ];
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'pimcore.data_collector';
    }

    /**
     * @return string|null
     */
    public function getContext()
    {
        return $this->data['context'];
    }
}
