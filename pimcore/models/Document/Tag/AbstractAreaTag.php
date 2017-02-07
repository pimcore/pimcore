<?php

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model\Document\Tag;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Tool;

abstract class AbstractAreaTag extends Tag
{
    /**
     * @param Info $info
     * @param array $params
     */
    protected function handleFrontend(Info $info, array $params)
    {
        // TODO inject area manager via DI when tags are built through container
        $areaManager = \Pimcore::getContainer()->get('pimcore.area_manager');
        $area        = $areaManager->get($info->getId());

        // assign parameters to view
        $this->view->getParameters()->add($params);

        // call action
        $area->action($info);

        if (null === $area->getViewTemplate()) {
            return;
        }

        // TODO inject templating via DI when tags are built through container
        $templating = \Pimcore::getContainer()->get('templating');
        $editmode   = $this->view->editmode;

        echo $area->getBrickHtmlTagOpen($info);

        if (null !== $area->getEditTemplate() && $editmode) {
            echo '<div class="pimcore_area_edit_button_' . $this->getName() . ' pimcore_area_edit_button"></div>';

            // forces the editmode in view independent if there's an edit or not
            if (!array_key_exists('forceEditInView', $params) || !$params['forceEditInView']) {
                $this->view->editmode = false;
            }
        }

        // render view template
        echo $templating->render(
            $area->getViewTemplate(),
            $this->view->getParameters()->all()
        );

        if (null !== $area->getEditTemplate() && $editmode) {
            $this->view->editmode = true;

            echo '<div class="pimcore_area_editmode_' . $this->getName() . ' pimcore_area_editmode pimcore_area_editmode_hidden">';

            // render edit template
            echo $templating->render(
                $area->getEditTemplate(),
                $this->view->getParameters()->all()
            );

            echo '</div>';
        }

        echo $area->getBrickHtmlTagClose($info);

        // call post render
        $area->postRenderAction($info);
    }

    /**
     * @param Info $info
     * @param array $params
     */
    protected function handleLegacyFrontend(Info $info, array $params)
    {
        $type = $info->getId();

        $this->getView()->brick = $info;

        $areas  = $this->getAreaDirs();
        $view   = $areas[$type] . '/view.php';
        $action = $areas[$type] . '/action.php';
        $edit   = $areas[$type] . '/edit.php';

        // assign parameters to view
        foreach ($params as $key => $value) {
            $this->getView()->assign($key, $value);
        }

        $actionObject = null;

        // check for action file
        if (is_file($action)) {
            include_once($action);

            $actionClassFound = true;

            $actionClass = preg_replace_callback("/[\-_][a-z]/", function ($matches) {
                $replacement = str_replace(["-", "_"], "", $matches[0]);

                return strtoupper($replacement);
            }, ucfirst($type));

            $actionClassname = "\\Pimcore\\Model\\Document\\Tag\\Area\\" . $actionClass;

            if (!Tool::classExists($actionClassname)) {
                // also check the legacy prefixed class name, as this is used by some plugins
                $actionClassname = "\\Document_Tag_Area_" . ucfirst($type);
                if (!Tool::classExists($actionClassname)) {
                    $actionClassFound = false;
                }
            }

            if ($actionClassFound) {
                $actionObject = new $actionClassname();

                if ($actionObject instanceof Area\AbstractArea) {
                    $actionObject->setView($this->getView());

                    $areaConfig = new \Zend_Config_Xml($areas[$type] . "/area.xml");
                    $actionObject->setConfig($areaConfig);

                    // params
                    $params = array_merge($this->view->getAllParams(), $params);
                    $actionObject->setParams($params);

                    if ($info) {
                        $actionObject->setBrick($info);
                    }

                    if (method_exists($actionObject, "action")) {
                        $actionObject->action();
                    }

                    $this->getView()->assign('actionObject', $actionObject);
                }
            } else {
                $this->getView()->assign('actionObject', null);
            }
        }

        if (is_file($view)) {
            $editmode = $this->getView()->editmode;

            if ($actionObject && method_exists($actionObject, "getBrickHtmlTagOpen")) {
                echo $actionObject->getBrickHtmlTagOpen($this);
            } else {
                echo '<div class="pimcore_area_' . $type . ' pimcore_area_content">';
            }

            if (is_file($edit) && $editmode) {
                echo '<div class="pimcore_area_edit_button_' . $this->getName() . ' pimcore_area_edit_button"></div>';

                // forces the editmode in view.php independent if there's an edit.php or not
                if (!array_key_exists("forceEditInView", $params) || !$params["forceEditInView"]) {
                    $this->getView()->editmode = false;
                }
            }

            $this->getView()->template($view);

            if (is_file($edit) && $editmode) {
                $this->getView()->editmode = true;

                echo '<div class="pimcore_area_editmode_' . $this->getName() . ' pimcore_area_editmode pimcore_area_editmode_hidden">';
                $this->getView()->template($edit);
                echo '</div>';
            }

            if ($actionObject && method_exists($actionObject, "getBrickHtmlTagClose")) {
                echo $actionObject->getBrickHtmlTagClose($this);
            } else {
                echo '</div>';
            }

            if ($actionObject && method_exists($actionObject, "postRenderAction")) {
                $actionObject->postRenderAction();
            }
        }
    }

    /**
     * @return array
     */
    abstract public function getAreaDirs();
}
