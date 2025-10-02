<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\DataCollector;

use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Version;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

/**
 * @internal
 */
class PimcoreDataCollector extends DataCollector implements ResetInterface
{
    public function __construct(
        protected PimcoreContextResolver $contextResolver
    ) {
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = [
            'version' => Version::getVersion(),
            'revision' => Version::getRevision(),
            'platform_version' => Version::getPlatformVersion(),
            'context' => $this->contextResolver->getPimcoreContext($request),
        ];
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function getName(): string
    {
        return 'pimcore';
    }

    public function getContext(): ?string
    {
        return $this->data['context'];
    }

    public function getVersion(): string
    {
        return $this->data['version'];
    }

    public function getRevision(): string
    {
        return $this->data['revision'];
    }

    public function getPlatformVersion(): ?string
    {
        return $this->data['platform_version'];
    }
}
