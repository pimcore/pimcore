<?php

namespace PimcoreBundle\Twig;

use Pimcore\Logger;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag;
use PimcoreBundle\View\ZendViewHelperBridge;

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
