<?php

declare(strict_types=1);

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

namespace Pimcore\Twig\Extension\Templating;

<<<<<<<< HEAD:lib/Twig/Extension/Templating/PimcoreUrl.php
use Pimcore\Http\RequestHelper;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Templating\Helper\HelperCharsetTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class PimcoreUrl implements RuntimeExtensionInterface
{
    use HelperCharsetTrait;
    /**
     * @var UrlGeneratorInterface
     */
    protected $generator;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @param UrlGeneratorInterface $generator
     * @param RequestHelper $requestHelper
     */
    public function __construct(UrlGeneratorInterface $generator, RequestHelper $requestHelper)
    {
        $this->generator = $generator;
        $this->requestHelper = $requestHelper;
    }
========
@trigger_error(
    'Pimcore\Templating\Helper\PimcoreUrl is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\PimcoreUrl::class . ' instead.',
    E_USER_DEPRECATED
);
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/PimcoreUrl.php

class_exists(\Pimcore\Twig\Extension\Templating\PimcoreUrl::class);

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\PimcoreUrl
     */
    class PimcoreUrl extends \Pimcore\Twig\Extension\Templating\PimcoreUrl {

<<<<<<<< HEAD:lib/Twig/Extension/Templating/PimcoreUrl.php
        // if name is an array, treat it as parameters
        if (is_array($name)) {
            if (is_array($parameters)) {
                $parameters = array_merge($name, $parameters);
            } else {
                $parameters = $name;
            }

            $name = null;
        }

        // get name from current route
        if (null === $name) {
            $name = $this->getCurrentRoute();
        }

        if (isset($parameters['object']) && $parameters['object'] instanceof Concrete) {
            $object = $parameters['object'];
            if ($linkGenerator = $object->getClass()->getLinkGenerator()) {
                unset($parameters['object']);
                $path = $linkGenerator->generate($object, [
                    'route' => $name,
                    'parameters' => $parameters,
                    'context' => $this,
                    'referenceType' => $referenceType,
                ]);

                return $path;
            }
        }

        if ($name !== null) {
            return $this->generator->generate($name, $parameters, $referenceType);
        }

        return '';
    }

    /**
     * Tries to get the current route name from current or master request
     *
     * @return string|null
     */
    protected function getCurrentRoute()
    {
        $route = null;

        if ($this->requestHelper->hasCurrentRequest()) {
            $route = $this->requestHelper->getCurrentRequest()->attributes->get('_route');
        }

        if (!$route && $this->requestHelper->hasMasterRequest()) {
            $route = $this->requestHelper->getMasterRequest()->attributes->get('_route');
        }

        return $route;
========
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/PimcoreUrl.php
    }
}
