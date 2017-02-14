<?php


namespace Pimcore\Bundle\PimcoreBundle\Templating\HelperBroker;

use Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine;
use Pimcore\Tool\RequestHelper;
use Symfony\Component\HttpFoundation\Request;

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
        'getParam',
        'getLocale',
        'getRequest',
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
        return call_user_func_array([$this, $method], $arguments);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    protected function getParam($key, $default = null)
    {
        $request = $this->requestHelper->getCurrentRequest();

        return $request->get($key, $default);
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
}
