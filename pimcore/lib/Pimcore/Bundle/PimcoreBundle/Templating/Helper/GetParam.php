<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Http\RequestHelper;
use Symfony\Component\Templating\Helper\Helper;

class GetParam extends Helper
{
    /**
     * @var RequestHelper
     */
    protected $requestHelper;

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
    public function getName()
    {
        return 'getParam';
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function __invoke($name, $default = null)
    {
        return $this->requestHelper->getCurrentRequest()->get($name, $default);
    }
}
