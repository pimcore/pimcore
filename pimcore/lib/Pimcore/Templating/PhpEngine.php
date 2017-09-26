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

namespace Pimcore\Templating;

use Pimcore\Config\Config;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Tag;
use Pimcore\Templating\Helper\Cache;
use Pimcore\Templating\Helper\Glossary;
use Pimcore\Templating\Helper\HeadLink;
use Pimcore\Templating\Helper\HeadMeta;
use Pimcore\Templating\Helper\HeadScript;
use Pimcore\Templating\Helper\HeadStyle;
use Pimcore\Templating\Helper\HeadTitle;
use Pimcore\Templating\Helper\InlineScript;
use Pimcore\Templating\Helper\Navigation;
use Pimcore\Templating\Helper\Placeholder\Container;
use Pimcore\Templating\HelperBroker\HelperBrokerInterface;
use Pimcore\Templating\Model\ViewModel;
use Pimcore\Templating\Model\ViewModelInterface;
use Pimcore\Tool\DeviceDetector;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\ActionsHelper;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\CodeHelper;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\FormHelper;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\RequestHelper;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\StopwatchHelper;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\TranslatorHelper;
use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine as BasePhpEngine;
use Symfony\Bundle\SecurityBundle\Templating\Helper\SecurityHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\Helper\SlotsHelper;
use Symfony\Component\Templating\Storage\Storage;

/**
 * Symfony PHP engine with pimcore additions:
 *
 *  - property access - $this->variable and $this->helper()
 *  - helper brokers integrate other view helpers (ZF) on __call
 *  - tag integration
 *
 * Defined in \Pimcore\Templating\HelperBroker\HelperShortcuts
 *
 * @method string getLocale()
 * @method Request getRequest()
 * @method SlotsHelper slots()
 * @method string path($name, $parameters = array(), $relative = false)
 * @method string url($name, $parameters = array(), $schemeRelative = false)
 * @method string t($key, $parameters = [], $domain = null, $locale = null)
 *
 * Symfony core helpers
 * @method ActionsHelper actions()
 * @method AssetsHelper assets()
 * @method CodeHelper code()
 * @method FormHelper form()
 * @method RequestHelper request()
 * @method RouterHelper router()
 * @method SecurityHelper security()
 * @method SessionHelper session()
 * @method StopwatchHelper stopwatch()
 * @method TranslatorHelper translator()
 *
 * Pimcore helpers
 * @method string action($action, $controller, $module, array $params = [])
 * @method Cache cache($name, $lifetime = null, $force = false)
 * @method DeviceDetector device($default = null)
 * @method array getAllParams()
 * @method mixed getParam($key, $default = null)
 * @method Glossary glossary()
 * @method Container placeholder($placeholderName)
 * @method HeadLink headLink(array $attributes = null, $placement = Container::APPEND)
 * @method HeadMeta headMeta($content = null, $keyValue = null, $keyType = 'name', $modifiers = array(), $placement = Container::APPEND)
 * @method HeadScript headScript($mode = HeadScript::FILE, $spec = null, $placement = 'APPEND', array $attrs = array(), $type = 'text/javascript')
 * @method HeadStyle headStyle($content = null, $placement = 'APPEND', $attributes = array())
 * @method HeadTitle headTitle($title = null, $setType = null)
 * @method string inc($include, array $params = [], $cacheEnabled = true, $editmode = null)
 * @method InlineScript inlineScript($mode = HeadScript::FILE, $spec = null, $placement = 'APPEND', array $attrs = array(), $type = 'text/javascript')
 * @method Navigation navigation()
 * @method Config|mixed websiteConfig($key = null, $default = null)
 * @method string pimcoreUrl(array $urlOptions = [], $name = null, $reset = false, $encode = true, $relative = false)
 * @method string translate($key, $parameters = [], $domain = null, $locale = null)
 *
 * Pimcore editables
 * @method Tag\Area area($name, $options = [])
 * @method Tag\Areablock areablock($name, $options = [])
 * @method Tag\Block block($name, $options = [])
 * @method Tag\Checkbox checkbox($name, $options = [])
 * @method Tag\Date date($name, $options = [])
 * @method Tag\Embed embed($name, $options = [])
 * @method Tag\Href href($name, $options = [])
 * @method Tag\Image image($name, $options = [])
 * @method Tag\Input input($name, $options = [])
 * @method Tag\Link link($name, $options = [])
 * @method Tag\Multihref multihref($name, $options = [])
 * @method Tag\Multiselect multiselect($name, $options = [])
 * @method Tag\Numeric numeric($name, $options = [])
 * @method Tag\Pdf pdf($name, $options = [])
 * @method Tag\Renderlet renderlet($name, $options = [])
 * @method Tag\Select select($name, $options = [])
 * @method Tag\Snippet snippet($name, $options = [])
 * @method Tag\Table table($name, $options = [])
 * @method Tag\Textarea textarea($name, $options = [])
 * @method Tag\Video video($name, $options = [])
 * @method Tag\Wysiwyg wysiwyg($name, $options = [])
 *
 * @property Document $document
 * @property bool $editmode
 * @property GlobalVariables $app
 */
class PhpEngine extends BasePhpEngine
{
    const PARAM_NO_PARENT = '_no_parent';

    /**
     * @var HelperBrokerInterface[]
     */
    protected $helperBrokers = [];

    /**
     * @var ViewModelInterface[]
     */
    protected $viewModels = [];

    /**
     * @param HelperBrokerInterface $helperBroker
     */
    public function addHelperBroker(HelperBrokerInterface $helperBroker)
    {
        $this->helperBrokers[] = $helperBroker;
    }

    /**
     * In addition to the core method, this keeps parameters in a ViewModel instance which is accessible from
     * view helpers and via $this->$variable.
     *
     * {@inheritdoc}
     */
    protected function evaluate(Storage $template, array $parameters = [])
    {
        // disable parent with "magic" _no_parent parameter
        $disableParent = false;
        if (isset($parameters[static::PARAM_NO_PARENT])) {
            $disableParent = (bool)($parameters[static::PARAM_NO_PARENT]);
            unset($parameters[static::PARAM_NO_PARENT]);
        }

        // create view model and push it onto the model stack
        $this->viewModels[] = new ViewModel($parameters);

        // render the template
        $result = parent::evaluate($template, $parameters);

        // remove current view model from stack and destroy it
        $viewModel = array_pop($this->viewModels);
        unset($viewModel);

        if ($disableParent) {
            $this->parents[$this->current] = null;
        }

        return $result;
    }

    /**
     * Renders template with current parameters
     *
     * @param $name
     * @param array $parameters
     *
     * @return string
     */
    public function template($name, array $parameters = [])
    {
        if ($viewModel = $this->getViewModel()) {
            // attach current variables
            $parameters = array_replace($viewModel->getParameters()->all(), $parameters);
        }

        return $this->render($name, $parameters);
    }

    /**
     * Get the current view model
     *
     * @return ViewModelInterface
     */
    public function getViewModel()
    {
        $count = count($this->viewModels);
        if ($count > 0) {
            return $this->viewModels[$count - 1];
        }
    }

    /**
     * Get a view model parameter
     *
     * @param $name
     * @param null $default
     *
     * @return mixed|null
     */
    public function getViewParameter($name, $default = null)
    {
        $viewModel = $this->getViewModel();

        if (null !== $viewModel) {
            return $viewModel->getParameters()->get($name, $default);
        }

        return $default;
    }

    /**
     * Magic getter reads variable from ViewModel
     *
     * @inheritDoc
     */
    public function __get($name)
    {
        return $this->getViewParameter($name);
    }

    /**
     * Magic isset checks variable from ViewModel
     *
     * @inheritDoc
     */
    public function __isset($name)
    {
        return $this->getViewParameter($name) !== null;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $viewModel = $this->getViewModel();
        if ($viewModel) {
            $viewModel->getParameters()->set($name, $value);
        } else {
            throw new \RuntimeException(sprintf('Can\'t set variable %s as there is no active view model', $name));
        }
    }

    /**
     * @inheritDoc
     */
    public function __call($method, $arguments)
    {
        // try to run helper from helper broker (native helper, document tag, zend view, ...)
        foreach ($this->helperBrokers as $helperBroker) {
            if ($helperBroker->supports($this, $method)) {
                return $helperBroker->helper($this, $method, $arguments);
            }
        }

        throw new \InvalidArgumentException('Call to undefined method ' . $method);
    }
}
