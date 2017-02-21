<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener\Traits;

use Pimcore\Bundle\PimcoreBundle\Service\Request\RequestContextResolver;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;

trait RequestContextAwareTrait
{
    /**
     * @var RequestContextResolver
     */
    protected $requestContextResolver;

    /**
     * @param RequestContextResolver $requestContextResolver
     */
    public function setRequestContextResolver($requestContextResolver)
    {
        $this->requestContextResolver = $requestContextResolver;
    }

    /**
     * Check if the request matches the given request context (e.g. admin)
     *
     * @param Request $request
     * @param string|array $context
     * @return bool
     */
    protected function matchesRequestContext(Request $request, $context)
    {
        if (null === $this->requestContextResolver) {
            throw new RuntimeException('Missing request context resolver. Is the listener properly configured?');
        }

        if (!is_array($context)) {
            if (!empty($context)) {
                $context = [$context];
            } else {
                $context = [];
            }
        }

        if (empty($context)) {
            throw new \InvalidArgumentException('Can\'t match against empty request context');
        }

        $resolvedContext = $this->requestContextResolver->getRequestContext($request);
        if (!$resolvedContext) {
            // no context available to match -> false
            return false;
        }

        foreach ($context as $ctx) {
            if ($ctx === $resolvedContext) {
                return true;
            }
        }

        return false;
    }
}
