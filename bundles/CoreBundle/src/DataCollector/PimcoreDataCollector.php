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

namespace Pimcore\Bundle\CoreBundle\DataCollector;

use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Tool;
use Pimcore\Version;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
class PimcoreDataCollector extends DataCollector implements ResetInterface
{
    public function __construct(
        protected PimcoreContextResolver $contextResolver,
        protected RouterInterface $router,
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = [
            'version' => Version::getVersion(),
            'revision' => Version::getRevision(),
            'context' => $this->contextResolver->getPimcoreContext($request),
        ];

        $element = $request->attributes->get('object') ?? $request->attributes->get(DynamicRouter::CONTENT_KEY);
        if($element instanceof ElementInterface) {
            $elementType = Service::getElementType($element);

            $url = Tool::getHostUrl() . $this->router->generate('pimcore_admin_login_deeplink');
            $url = sprintf("$url?%s", join("_", [$elementType, $element->getId(), $element->getType()]));

            $this->data['deeplink'] = [
                'label' => $element->getKey() ?: ($element->getId() === 1 ? 'Home' : $element->getFullPath()),
                'href' => $url
            ];

            $this->data['elementId'] = $element->getId();
        }
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

    public function getDeeplink(): ?array
    {
        return $this->data['deeplink'] ?? null;
    }

    public function getElementId(): int|string|null
    {
        return $this->data['elementId'] ?? null;
    }
}
