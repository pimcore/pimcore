<?php

namespace Pimcore\Bundle\PimcoreLegacyBundle\Document\Tag;

use Pimcore\Document\Tag\TagHandlerInterface;
use Pimcore\ExtensionManager;
use Pimcore\Model\Document\Tag;
use Pimcore\Model\Document\Tag\Area\AbstractArea;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Tool;
use Pimcore\Translate;
use Pimcore\View;

class LegacyTagHandler implements TagHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($view)
    {
        return $view instanceof View;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableAreablockAreas(Tag\Areablock $tag, array $options)
    {
        /** @var View $view */
        $view = $tag->getView();

        // read available types
        $areaConfigs = $tag->getBrickConfigs();

        $availableAreas = [];
        foreach ($areaConfigs as $areaName => $areaConfig) {
            // don't show disabled bricks
            if (!isset($options['dontCheckEnabled']) || !$options['dontCheckEnabled']) {
                if (!$tag->isBrickEnabled($areaName)) {
                    continue;
                }
            }

            if (empty($options["allowed"]) || in_array($areaName, $options["allowed"])) {
                $n = (string)$areaConfig->name;
                $d = (string)$areaConfig->description;

                $icon = (string)$areaConfig->icon;

                if ($view->editmode) {
                    if (empty($icon)) {
                        $path     = $tag->getPathForBrick($areaName);
                        $iconPath = $path . "/icon.png";

                        if (file_exists($iconPath)) {
                            $icon = str_replace(PIMCORE_DOCUMENT_ROOT, "", $iconPath);
                        }
                    }

                    $n = Translate::transAdmin((string)$areaConfig->name);
                    $d = Translate::transAdmin((string)$areaConfig->description);
                }

                $availableAreas[$areaName] = [
                    "name"        => $n,
                    "description" => $d,
                    "type"        => $areaName,
                    "icon"        => $icon,
                ];
            }
        }

        return $availableAreas;
    }

    /**
     * {@inheritdoc}
     */
    public function renderAreaFrontend(Info $info, array $params)
    {
        $tag  = $info->getTag();
        $type = $info->getId();

        // prepare info object
        $info->setName($tag->getName());
        $info->setPath(str_replace(PIMCORE_DOCUMENT_ROOT, '', ExtensionManager::getPathForExtension($type, 'brick')));
        $info->setConfig(ExtensionManager::getBrickConfig($type));

        /** @var View $view */
        $view = $tag->getView();
        $view->brick = $info;

        $areas        = $tag->getAreaDirs();
        $viewScript   = $areas[$type] . '/view.php';
        $actionScript = $areas[$type] . '/action.php';
        $editScript   = $areas[$type] . '/edit.php';

        // assign parameters to view
        foreach ($params as $key => $value) {
            $view->assign($key, $value);
        }

        $actionObject = null;

        // check for action file
        if (is_file($actionScript)) {
            include_once($actionScript);

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

                if ($actionObject instanceof AbstractArea) {
                    $actionObject->setView($view);

                    $areaConfig = new \Pimcore\Config\Config($areas[$type] . "/area.xml");
                    $actionObject->setConfig($areaConfig);

                    // params
                    $params = array_merge($view->getAllParams(), $params);
                    $actionObject->setParams($params);

                    if ($info) {
                        $actionObject->setBrick($info);
                    }

                    if (method_exists($actionObject, "action")) {
                        $actionObject->action();
                    }

                    $view->assign('actionObject', $actionObject);
                }
            } else {
                $view->assign('actionObject', null);
            }
        }

        if (is_file($viewScript)) {
            $editmode = $view->editmode;

            if ($actionObject && method_exists($actionObject, "getBrickHtmlTagOpen")) {
                echo $actionObject->getBrickHtmlTagOpen($this);
            } else {
                echo '<div class="pimcore_area_' . $type . ' pimcore_area_content">';
            }

            if (is_file($editScript) && $editmode) {
                echo '<div class="pimcore_area_edit_button_' . $tag->getName() . ' pimcore_area_edit_button"></div>';

                // forces the editmode in view.php independent if there's an edit.php or not
                if (!array_key_exists("forceEditInView", $params) || !$params["forceEditInView"]) {
                    $view->editmode = false;
                }
            }

            $view->template($viewScript);

            if (is_file($editScript) && $editmode) {
                $view->editmode = true;

                echo '<div class="pimcore_area_editmode_' . $tag->getName() . ' pimcore_area_editmode pimcore_area_editmode_hidden">';
                $view->template($editScript);
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
     * {@inheritdoc}
     */
    public function renderAction($view, $controller, $action, $parent = null, array $params = [])
    {
        /** @var View $view */
        return $view->action(
            $action,
            $controller,
            $parent,
            $params
        );
    }
}
