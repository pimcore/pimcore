<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Http\RequestHelper;
use Symfony\Component\Templating\Helper\Helper;

class GetAllParams extends Helper
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
        return 'GetAllParams';
    }

    /**
     * @return array
     */
    public function __invoke()
    {
        $request = $this->requestHelper->getCurrentRequest();
        return array_merge($request->request->all(), $request->query->all());
    }
}
