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

namespace PimcoreLegacyBundle\Templating\Renderer;


use PimcoreLegacyBundle\HttpKernel\Kernel as LegacyKernel;
use Pimcore\Model\Document;
use Pimcore\View;

class DocumentRenderer {

    /**
     * @var LegacyKernel
     */
    protected $legacyKernel;


    public function __construct(LegacyKernel $legacyKernel)
    {
        $this->legacyKernel = $legacyKernel;
    }

    /**
     * renders document and returns rendered result as string
     *
     * @param Document $document
     * @param array $params
     * @param bool $useLayout
     * @return string
     */
    public function render(Document $document, $params = [], $useLayout = false) {

        $this->legacyKernel->boot();

        $layout = null;
        $existingActionHelper = null;

        if (\Zend_Controller_Action_HelperBroker::hasHelper("layout")) {
            $existingActionHelper = \Zend_Controller_Action_HelperBroker::getExistingHelper("layout");
        }
        $layoutInCurrentAction = (\Zend_Layout::getMvcInstance() instanceof \Zend_Layout) ? \Zend_Layout::getMvcInstance()->getLayout() : false;

        $viewHelper = \Zend_Controller_Action_HelperBroker::getExistingHelper("ViewRenderer");
        if ($viewHelper) {
            if ($viewHelper->view === null) {
                $viewHelper->initView(PIMCORE_WEBSITE_PATH . "/views");
            }
            $view = $viewHelper->view;
        } else {
            $view = new \Pimcore\View();
        }

        // add the view script path from the website module to the view, because otherwise it's not possible to call
        // this method out of other modules to render documents, eg. sending e-mails out of an plugin with Pimcore_Mail
        $moduleDirectory = \Zend_Controller_Front::getInstance()->getModuleDirectory($document->getModule());
        if (!empty($moduleDirectory)) {
            $view->addScriptPath($moduleDirectory . "/views/layouts");
            $view->addScriptPath($moduleDirectory . "/views/scripts");
        } else {
            $view->addScriptPath(PIMCORE_FRONTEND_MODULE . "/views/layouts");
            $view->addScriptPath(PIMCORE_FRONTEND_MODULE . "/views/scripts");
        }

        $params["document"] = $document;
        $viewParamsBackup = [];
        foreach ($params as $key => $value) {
            if ($view->$key) {
                $viewParamsBackup[$key] = $view->$key;
            }
            $view->$key = $value;
        }
        $view->document = $document;

        if ($useLayout) {
            if (!$layout = \Zend_Layout::getMvcInstance()) {
                $layout = \Zend_Layout::startMvc();
                $layout->setViewSuffix(View::getViewScriptSuffix());
                if ($layoutHelper = $view->getHelper("layout")) {
                    $layoutHelper->setLayout($layout);
                }
            }
            $layout->setLayout("--modification-indicator--");
        }

        $content = $view->action($document->getAction(), $document->getController(), $document->getModule(), $params);

        //has to be called after $view->action so we can determine if a layout is enabled in $view->action()
        if ($useLayout) {
            if ($layout instanceof \Zend_Layout) {
                $layout->{$layout->getContentKey()} = $content;
                if (is_array($params)) {
                    foreach ($params as $key => $value) {
                        $layout->getView()->$key = $value;
                    }
                }

                // when using Document\Service::render() you have to set a layout in the view ($this->layout()->setLayout("mylayout"))
                if ($layout->getLayout() != "--modification-indicator--") {
                    $content = $layout->render();
                }

                //deactivate the layout if it was not activated in the called action
                //otherwise we would activate the layout in the called action
                \Zend_Layout::resetMvcInstance();
                if (!$layoutInCurrentAction) {
                    $layout->disableLayout();
                } else {
                    $layout = \Zend_Layout::startMvc();
                    $layout->setViewSuffix(View::getViewScriptSuffix()); // set pimcore specifiy view suffix
                    $layout->setLayout($layoutInCurrentAction);
                    $view->getHelper("Layout")->setLayout($layout);

                    if ($existingActionHelper) {
                        \Zend_Controller_Action_HelperBroker::removeHelper("layout");
                        \Zend_Controller_Action_HelperBroker::addHelper($existingActionHelper);

                        $pluginClass = $layout->getPluginClass();
                        $front = $existingActionHelper->getFrontController();
                        if ($front->hasPlugin($pluginClass)) {
                            $plugin = $front->getPlugin($pluginClass);
                            $plugin->setLayoutActionHelper($existingActionHelper);
                        }
                    }
                }
                $layout->{$layout->getContentKey()} = null; //reset content
            }
        }

        if (\Pimcore\Config::getSystemConfig()->outputfilters->less) {
            $content = \Pimcore\Tool\Less::processHtml($content);
        }

        foreach ($viewParamsBackup as $key => $value) {
            $view->$key = $value;
        }

        return $content;
    }

}
