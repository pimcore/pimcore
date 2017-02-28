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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Pimcore\Model;

class View extends \Zend_View
{
    /**
     * @var string
     */
    protected static $viewScriptSuffix = "php";

    /**
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * @param $type
     * @param $realName
     * @param array $options
     * @return Model\Document\Tag
     */
    public function tag($type, $realName, $options = [])
    {
        $type = strtolower($type);
        $document = $this->document;
        $name = Model\Document\Tag::buildTagName($type, $realName, $document);

        try {
            if ($document instanceof Model\Document\PageSnippet) {
                $tag = $document->getElement($name);
                if ($tag instanceof Model\Document\Tag && $tag->getType() == $type) {

                    // call the load() method if it exists to reinitialize the data (eg. from serializing, ...)
                    if (method_exists($tag, "load")) {
                        $tag->load();
                    }

                    // set view & controller, editmode
                    $tag->setController($this->controller);
                    $tag->setView($this);
                    $tag->setEditmode($this->editmode);

                    $tag->setOptions($options);
                } else {
                    $tag = Model\Document\Tag::factory($type, $name, $document->getId(), $options, $this->controller, $this, $this->editmode);
                    $document->setElement($name, $tag);
                }

                // set the real name of this editable, without the prefixes and suffixes from blocks and areablocks
                $tag->setRealName($realName);
            }

            return $tag;
        } catch (\Exception $e) {
            Logger::warning($e);
        }
    }

    /**
     * @param string $script
     */
    public function includeTemplateFile($script)
    {
        $showTemplatePaths = isset($_REQUEST["pimcore_show_template_paths"]);
        if ($showTemplatePaths && \Pimcore::inDebugMode()) {
            echo "\n<!-- start template inclusion: " . $script . " -->\n";
        }
        include($script);
        if ($showTemplatePaths && \Pimcore::inDebugMode()) {
            echo "\n<!-- finished template inclusion: " . $script . " -->\n";
        }
    }

    /**
     * @param $scriptPath
     * @param array $params
     * @param bool $resetPassedParams
     * @param bool $capture
     * @return string
     */
    public function template($scriptPath, $params = [], $resetPassedParams = false, $capture = false)
    {
        foreach ($params as $key => $value) {
            $this->assign($key, $value);
        }

        if ($capture) {
            $captureKey = (is_string($capture)) ? $capture : 'pimcore_capture_template';
            $this->placeholder($captureKey)->captureStart(\Zend_View_Helper_Placeholder_Container_Abstract::SET);
        }

        $found = false;
        $paths = $this->getScriptPaths();
        $paths[] = PIMCORE_DOCUMENT_ROOT;

        foreach ($paths as $path) {
            $p = $path . $scriptPath;
            if (is_file($p) && !$found) {
                $found = true;
                $this->includeTemplateFile($p);
                break;
            }
        }

        if (!$found) {
            if (is_file($scriptPath)) {
                $found = true;
                $this->includeTemplateFile($scriptPath);
            }
        }

        if ($resetPassedParams) {
            foreach ($params as $key => $value) {
                $this->$key = null;
            }
        }

        if ($capture) {
            $this->placeholder($captureKey)->captureEnd();

            return trim($this->placeholder($captureKey)->getValue());
        }
    }

    /**
     * includes a document
     *
     * @param $include
     * @param array $params
     * @param bool $cacheEnabled
     * @return string
     */
    public function inc($include, $params = null, $cacheEnabled = true)
    {
        if (!is_array($params)) {
            $params = [];
        }

        $renderer = \Pimcore::getContainer()->get('pimcore.templating.include_renderer');
        $content  = $renderer->render($include, $params, $this->editmode, $cacheEnabled, $this);

        return $content;
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        $value = $this->getRequest()->getParam($key);
        if ((null === $value || '' === $value) && (null !== $default)) {
            $value = $default;
        }

        return $value;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function _getParam($key, $default = null)
    {
        return $this->getParam($key, $default);
    }

    /**
     * @return array
     */
    public function getAllParams()
    {
        return $this->getRequest()->getParams();
    }

    /**
     * @deprecated
     * @return array
     */
    public function _getAllParams()
    {
        return $this->getAllParams();
    }

    /**
     * @return \Zend_Controller_Request_Http
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @return $this
     */
    public function setRequest(\Zend_Controller_Request_Abstract $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * shorthand for $this->translate() view helper
     */
    public function t()
    {
        return call_user_func_array([$this, "translate"], func_get_args());
    }

    /**
     * shorthand for $this->translateAdmin() view helper
     */
    public function ts()
    {
        return call_user_func_array([$this, "translateAdmin"], func_get_args());
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed|Model\Document\Tag|string
     * @throws \Exception
     */
    public function __call($method, $arguments)
    {
        $class = "\\Pimcore\\Model\\Document\\Tag\\" . ucfirst(strtolower($method));

        $classFound = true;
        if (!\Pimcore\Tool::classExists($class)) {
            $oldStyleClass = "Document_Tag_" . ucfirst(strtolower($method));
            if (!\Pimcore\Tool::classExists($oldStyleClass)) {
                $classFound = false;
            }
        }

        if ($classFound) {
            if (!isset($arguments[0])) {
                throw new \Exception("You have to set a name for the called tag (editable): " . $method);
            }

            // set default if there is no editable configuration provided
            if (!isset($arguments[1])) {
                $arguments[1] = [];
            }

            return $this->tag($method, $arguments[0], $arguments[1]);
        }

        if ($this->document instanceof Model\Document) {
            if (method_exists($this->document, $method)) {
                return call_user_func_array([$this->document, $method], $arguments);
            }
        }

        return parent::__call($method, $arguments);
    }

    /**
     * @static
     * @return string
     */
    public static function getViewScriptSuffix()
    {
        return self::$viewScriptSuffix;
    }

    /**
     * @param $suffix
     */
    public static function setViewScriptSuffix($suffix) {
        self::$viewScriptSuffix = $suffix;
    }
}
