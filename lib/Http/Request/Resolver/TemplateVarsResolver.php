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

namespace Pimcore\Http\Request\Resolver;

use Pimcore\Templating\Vars\TemplateVarsProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @deprecated
 */
class TemplateVarsResolver extends AbstractRequestResolver
{
    /**
     * @var TemplateVarsProviderInterface[]
     */
    protected $providers = [];

    /**
     * @param RequestStack $requestStack
     * @param TemplateVarsProviderInterface[] $providers
     */
    public function __construct(RequestStack $requestStack, array $providers = [])
    {
        parent::__construct($requestStack);

        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    /**
     * Add a template vars provider
     *
     * @param TemplateVarsProviderInterface $provider
     */
    public function addProvider(TemplateVarsProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param Request|null $request
     *
     * @return array
     */
    public function getTemplateVars(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        $vars = [];
        foreach ($this->providers as $provider) {
            $vars = $provider->addTemplateVars($request, $vars);

            if (!is_array($vars)) {
                throw new \RuntimeException(
                    sprintf(
                        'Expected TemplateVarsProvider %s to return an array (%s returned)',
                        get_class($provider),
                        gettype($vars)
                    )
                );
            }
        }

        return $vars;
    }
}
