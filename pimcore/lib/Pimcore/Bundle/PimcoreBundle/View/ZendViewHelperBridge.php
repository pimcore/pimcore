<?php

namespace Pimcore\Bundle\PimcoreBundle\View;

use Pimcore\Bundle\PimcoreBundle\Service\Request\TemplateVarsResolver;

class ZendViewHelperBridge
{
    /**
     * @var ZendViewProvider
     */
    protected $viewProvider;

    /**
     * @var TemplateVarsResolver
     */
    protected $varsResolver;

    /**
     * @param ZendViewProvider $viewProvider
     */
    public function __construct(ZendViewProvider $viewProvider, TemplateVarsResolver $varsResolver)
    {
        $this->viewProvider = $viewProvider;
        $this->varsResolver = $varsResolver;
    }

    /**
     * Get Zend View helper instance
     *
     * @param string $helperName
     * @param \Zend_View $view
     * @return \Zend_View_Helper_Interface
     */
    public function getZendViewHelper($helperName, \Zend_View $view = null)
    {
        if (null === $view) {
            $view = $this->viewProvider->getView();
        }

        /** @var \Zend_View_Helper_Interface $helper */
        $helper = $view->getHelper($helperName);

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
        $view = $this->viewProvider->createView();

        // set global variables (document, editmode) on the new view
        foreach ($this->varsResolver->getTemplateVars() as $key => $value) {
            $view->$key = $value;
        }

        return call_user_func_array([$view, $helperName], $arguments);
    }
}
