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

namespace Pimcore\Bundle\PimcoreBundle\Templating\HelperBroker;

use Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine;
use Pimcore\Http\RequestHelper;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shortcuts available as $this->method() on the engine
 */
class HelperShortcuts implements HelperBrokerInterface
{
    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * Supported methods
     * @var array
     */
    protected $shortcuts = [
        'getLocale',
        'getRequest',
        'path',
        'url'
    ];

    /**
     * @param RequestHelper $requestHelper
     */
    public function __construct(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }

    /**
     * @inheritDoc
     */
    public function supports(PhpEngine $engine, $method)
    {
        return in_array($method, $this->shortcuts);
    }

    /**
     * @inheritDoc
     */
    public function helper(PhpEngine $engine, $method, array $arguments)
    {
        return call_user_func_array([$this, $method], [$engine, $arguments]);
    }

    /**
     * @return string
     */
    protected function getLocale()
    {
        return $this->requestHelper->getCurrentRequest()->getLocale();
    }

    /**
     * @return Request
     */
    protected function getRequest()
    {
        return $this->requestHelper->getCurrentRequest();
    }

    /**
     * @param PhpEngine $engine
     * @param array $arguments
     * @return string
     */
    protected function url(PhpEngine $engine, array $arguments)
    {
        /** @var RouterHelper $helper */
        $helper = $engine->get('router');

        return call_user_func_array([$helper, 'url'], $arguments);
    }

    /**
     * @param PhpEngine $engine
     * @param array $arguments
     * @return string
     */
    protected function path(PhpEngine $engine, array $arguments)
    {
        /** @var RouterHelper $helper */
        $helper = $engine->get('router');

        return call_user_func_array([$helper, 'path'], $arguments);
    }
}
