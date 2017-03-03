<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Renderer;


use Pimcore\Cache;
use Pimcore\Model;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element;
use Pimcore\Tool\DeviceDetector;
use Pimcore\Tool\Frontend;
use Pimcore\View;

class IncludeRenderer
{
    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;

    /**
     * @param ActionRenderer $actionRenderer
     */
    public function __construct(ActionRenderer $actionRenderer)
    {
        $this->actionRenderer = $actionRenderer;
    }

    /**
     * Renders a document include. Currently handles both legacy and new rendering.
     *
     * TODO move legacy part to legacy bundle
     *
     * @param $include
     * @param array $params
     * @param bool $editmode
     * @param bool $cacheEnabled
     * @param View $legacyView
     *
     * @return string
     */
    public function render($include, array $params = [], $editmode = false, $cacheEnabled = true, View $legacyView = null)
    {
        if (!is_array($params)) {
            $params = [];
        }

        // check if output-cache is enabled, if so, we're also using the cache here
        $cacheKey    = null;
        $cacheConfig = false;

        if ($cacheEnabled) {
            if ($cacheConfig = Frontend::isOutputCacheEnabled()) {
                // cleanup params to avoid serializing Element\ElementInterface objects
                $cacheParams = $params;
                $cacheParams["~~include-document"] = $include;

                array_walk($cacheParams, function (&$value, $key) {
                    if ($value instanceof Element\ElementInterface) {
                        $value = $value->getId();
                    } elseif (is_object($value) && method_exists($value, "__toString")) {
                        $value = (string)$value;
                    }
                });

                $cacheKey = "tag_inc__" . md5(serialize($cacheParams));
                if ($content = Cache::load($cacheKey)) {
                    return $content;
                }
            }
        }

        // TODO remove dependency on registry setting
        $editmodeBackup = false;
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_editmode')) {
            $editmodeBackup = \Pimcore\Cache\Runtime::get("pimcore_editmode");
        }

        \Pimcore\Cache\Runtime::set("pimcore_editmode", false);

        $includeBak = $include;

        // this is if $this->inc is called eg. with $this->href() as argument
        if (!$include instanceof PageSnippet && is_object($include) && method_exists($include, "__toString")) {
            $include = (string)$include;
        }

        if (is_numeric($include)) {
            try {
                $include = Model\Document::getById($include);
            } catch (\Exception $e) {
                $include = $includeBak;
            }
        } elseif (is_string($include)) {
            try {
                $include = Model\Document::getByPath($include);
            } catch (\Exception $e) {
                $include = $includeBak;
            }
        }

        $params  = array_merge($params, ["document" => $include]);
        $content = "";

        if ($include instanceof PageSnippet && $include->isPublished()) {
            // TODO move this to delegating structure and add Pimcore\View support from legacy bundle
            if (null !== $legacyView) {
                $content = $this->renderLegacyAction($legacyView, $include, $params);
            } else {
                $content = $this->renderAction($include, $params);
            }

            if ($editmode) {
                $content = $this->modifyEditmodeContent($include, $content);
            }
        }

        \Pimcore\Cache\Runtime::set("pimcore_editmode", $editmodeBackup);

        // write contents to the cache, if output-cache is enabled
        if ($cacheConfig && !DeviceDetector::getInstance()->wasUsed()) {
            Cache::save($content, $cacheKey, ["output", "output_inline"], $cacheConfig["lifetime"]);
        }

        return $content;
    }

    /**
     * @param PageSnippet $include
     * @param $params
     * @return string
     */
    protected function renderAction(PageSnippet $include, $params)
    {
        $controller = $this->actionRenderer->createDocumentReference($include, $params);

        return $this->actionRenderer->render($controller);
    }

    /**
     * @param View $view
     * @param PageSnippet $include
     * @param $params
     * @return string
     */
    protected function renderLegacyAction(View $view, PageSnippet $include, $params)
    {
        $content = '';

        if ($include->getAction() && $include->getController()) {
            $content = $view->action(
                $include->getAction(),
                $include->getController(),
                $include->getModule(),
                $params
            );
        } elseif ($include->getTemplate()) {
            $content = $view->action("default", "default", null, $params);
        }

        return $content;
    }

    /**
     * in editmode, we need to parse the returned html from the document include
     * add a class and the pimcore id / type so that it can be opened in editmode using the context menu
     * if there's no first level HTML container => add one (wrapper)
     *
     * @param PageSnippet $include
     * @param $content
     * @return string
     */
    protected function modifyEditmodeContent(PageSnippet $include, $content)
    {
        include_once("simple_html_dom.php");

        $editmodeClass = " pimcore_editable pimcore_tag_inc ";

        // this is if the content that is included does already contain markup/html
        // this is needed by the editmode to highlight included documents
        if ($html = str_get_html($content)) {
            $childs = $html->find("*");
            if (is_array($childs)) {
                foreach ($childs as $child) {
                    $child->class        = $child->class . $editmodeClass;
                    $child->pimcore_type = $include->getType();
                    $child->pimcore_id   = $include->getId();
                }
            }
            $content = $html->save();

            $html->clear();
            unset($html);
        } else {
            // add a div container if the include doesn't contain markup/html
            $content = '<div class="' . $editmodeClass . '" pimcore_id="' . $include->getId() . '" pimcore_type="' . $include->getType() . '">' . $content . '</div>';
        }

        return $content;
    }
}
