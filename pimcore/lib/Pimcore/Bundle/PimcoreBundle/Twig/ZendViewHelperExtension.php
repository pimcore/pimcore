<?php

namespace Pimcore\Bundle\PimcoreBundle\Twig;

use Pimcore\Bundle\PimcoreBundle\View\ZendViewHelperBridge;

class ZendViewHelperExtension extends \Twig_Extension
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
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('zend_*', [$this, 'zendViewHelper'], [
                'is_safe'     => ['html'],
                'is_variadic' => true
            ])
        ];
    }

    // HACK HACK the ignore filter is just a hack until i found out how to register the function
    // above so that it can be called with {% %} instead of {{ }}
    public function getFilters()
    {
        return [
            // ignore the output
            new \Twig_Filter('ignore', function($input) {
                return '';
            })
        ];
    }

    /**
     * @param $name
     * @param array $arguments
     * @return string
     */
    public function zendViewHelper($name, array $arguments = [])
    {
        return $this->zendViewHelperBridge->execute($name, $arguments);
    }
}
