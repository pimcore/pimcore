<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Bundle\PimcoreBundle\View\ZendViewHelperBridge;
use Symfony\Component\Templating\Helper\Helper;

class ZendViewHelper extends Helper
{
    /**
     * @var ZendViewHelperBridge
     */
    protected $zendViewHelperBridge;

    /**
     * @param ZendViewHelperBridge $zendViewHelperBridge
     */
    public function __construct(ZendViewHelperBridge $zendViewHelperBridge)
    {
        $this->zendViewHelperBridge = $zendViewHelperBridge;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'zendViewHelper';
    }

    /**
     * @param $name
     * @param array ...$arguments
     * @return mixed
     */
    public function render($name, ...$arguments)
    {
        return $this->zendViewHelperBridge->execute($name, $arguments);
    }
}
