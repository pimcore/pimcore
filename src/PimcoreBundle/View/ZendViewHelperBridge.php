<?php

namespace PimcoreBundle\View;

class ZendViewHelperBridge
{
    /**
     * @var ZendViewProvider
     */
    protected $viewProvider;

    /**
     * @var \Zend_View_Helper_Interface[]
     */
    protected $helpers = [];

    /**
     * @param ZendViewProvider $viewProvider
     */
    public function __construct(ZendViewProvider $viewProvider)
    {
        $this->viewProvider = $viewProvider;
    }

    /**
     * Get Zend View helper instance
     *
     * @param string $helperName
     * @return \Zend_View_Helper_Interface
     */
    public function getZendViewHelper($helperName)
    {
        if (isset($this->helpers[$helperName])) {
            return $this->helpers[$helperName];
        }

        $view   = $this->viewProvider->createView();
        $helper = $view->getHelper($helperName);

        if ($helper && $helper instanceof \Zend_View_Helper_Interface) {
            $this->helpers[$helperName] = $helper;
        } else {
            throw new \RuntimeException(sprintf('Unable to load Zend View Helper "%s"', $helperName));
        }

        $this->helpers[$helperName] = $helper;

        return $helper;
    }

    /**
     * Execute a Zend View helper with the given arguments
     *
     * @param $helperName
     * @param array $arguments
     * @return mixed
     */
    public function execute($helperName, array $arguments = [])
    {
        $helper    = $this->getZendViewHelper($helperName);
        $reflector = new \ReflectionClass($helper);
        $method    = $helperName;

        if (!$reflector->hasMethod($method)) {
            throw new \RuntimeException(sprintf(
                'Zend View helper "%s" (implemented in %s) does not define a method "%s"',
                $helperName,
                $reflector->getName(),
                $method
            ));
        }

        $result = call_user_func_array([$helper, $method], $arguments);

        return $result;
    }
}
