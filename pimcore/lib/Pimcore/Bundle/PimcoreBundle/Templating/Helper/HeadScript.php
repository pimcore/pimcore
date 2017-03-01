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

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_View_Helper_HeadScript
 * ----------------------------------------------------------------------------------
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id$
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Bundle\PimcoreBundle\Templating\Helper\Placeholder\CacheBusterAware;
use Pimcore\Bundle\PimcoreBundle\Templating\Helper\Placeholder\Container;
use Pimcore\Bundle\PimcoreBundle\Templating\Helper\Placeholder\ContainerService;

/**
 * Helper for setting and retrieving script elements for HTML head section
 *
 * @uses       Zend_View_Helper_Placeholder_Container_Standalone
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @method $this appendFile($src, $type = 'text/javascript', array $attrs = array())
 * @method $this appendScript($script, $type = 'text/javascript', array $attrs = array())
 * @method $this offsetSetFile($index, $src, $type = 'text/javascript', array $attrs = array())
 * @method $this offsetSetScript($index, $script, $type = 'text/javascript', array $attrs = array())
 * @method $this prependFile($src, $type = 'text/javascript', array $attrs = array())
 * @method $this prependScript($script, $type = 'text/javascript', array $attrs = array())
 * @method $this setFile($src, $type = 'text/javascript', array $attrs = array())
 * @method $this setScript($script, $type = 'text/javascript', array $attrs = array())
 */
class HeadScript extends CacheBusterAware
{
    /**#@+
     * Script type contants
     * @const string
     */
    const FILE   = 'FILE';
    const SCRIPT = 'SCRIPT';
    /**#@-*/

    /**
     * Registry key for placeholder
     * @var string
     */
    protected $_regKey = 'HeadScript';

    /**
     * Are arbitrary attributes allowed?
     * @var bool
     */
    protected $_arbitraryAttributes = false;

    /**#@+
     * Capture type and/or attributes (used for hinting during capture)
     * @var string
     */
    protected $_captureLock;
    protected $_captureScriptType  = null;
    protected $_captureScriptAttrs = null;
    protected $_captureType;
    /**#@-*/

    /**
     * Optional allowed attributes for script tag
     * @var array
     */
    protected $_optionalAttributes = array(
        'charset', 'defer', 'language', 'src'
    );

    /**
     * Required attributes for script tag
     * @var string
     */
    protected $_requiredAttributes = array('type');

    /**
     * Whether or not to format scripts using CDATA; used only if doctype
     * helper is not accessible
     * @var bool
     */
    public $useCdata = false;

    /**
     * HeadScript constructor.
     *
     * Set separator to PHP_EOL.
     *
     * @param ContainerService $containerService
     */
    public function __construct(ContainerService $containerService)
    {
        parent::__construct($containerService);
        $this->setSeparator(PHP_EOL);
    }


    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'headScript';
    }


    /**
     * Return headScript object
     *
     * Returns headScript helper object; optionally, allows specifying a script
     * or script file to include.
     *
     * @param  string $mode Script or file
     * @param  string $spec Script/url
     * @param  string $placement Append, prepend, or set
     * @param  array $attrs Array of script attributes
     * @param  string $type Script type and/or array of script attributes
     * @return HeadScript
     */
    public function __invoke($mode = HeadScript::FILE, $spec = null, $placement = 'APPEND', array $attrs = array(), $type = 'text/javascript')
    {
        if ((null !== $spec) && is_string($spec)) {
            $action    = ucfirst(strtolower($mode));
            $placement = strtolower($placement);
            switch ($placement) {
                case 'set':
                case 'prepend':
                case 'append':
                    $action = $placement . $action;
                    break;
                default:
                    $action = 'append' . $action;
                    break;
            }
            $this->$action($spec, $type, $attrs);
        }

        return $this;
    }

    /**
     * Start capture action
     *
     * @param  mixed $captureType
     * @param  string $typeOrAttrs
     * @return void
     */
    public function captureStart($captureType = Container::APPEND, $type = 'text/javascript', $attrs = array())
    {
        if ($this->_captureLock) {
            throw new Exception('Cannot nest headScript captures');
        }

        $this->_captureLock        = true;
        $this->_captureType        = $captureType;
        $this->_captureScriptType  = $type;
        $this->_captureScriptAttrs = $attrs;
        ob_start();
    }

    /**
     * End capture action and store
     *
     * @return void
     */
    public function captureEnd()
    {
        $content                   = ob_get_clean();
        $type                      = $this->_captureScriptType;
        $attrs                     = $this->_captureScriptAttrs;
        $this->_captureScriptType  = null;
        $this->_captureScriptAttrs = null;
        $this->_captureLock        = false;

        switch ($this->_captureType) {
            case Container::SET:
            case Container::PREPEND:
            case Container::APPEND:
                $action = strtolower($this->_captureType) . 'Script';
                break;
            default:
                $action = 'appendScript';
                break;
        }
        $this->$action($content, $type, $attrs);
    }

    /**
     * Overload method access
     *
     * Allows the following method calls:
     * - appendFile($src, $type = 'text/javascript', $attrs = array())
     * - offsetSetFile($index, $src, $type = 'text/javascript', $attrs = array())
     * - prependFile($src, $type = 'text/javascript', $attrs = array())
     * - setFile($src, $type = 'text/javascript', $attrs = array())
     * - appendScript($script, $type = 'text/javascript', $attrs = array())
     * - offsetSetScript($index, $src, $type = 'text/javascript', $attrs = array())
     * - prependScript($script, $type = 'text/javascript', $attrs = array())
     * - setScript($script, $type = 'text/javascript', $attrs = array())
     *
     * @param  string $method
     * @param  array $args
     * @return HeadScript
     * @throws Exception if too few arguments or invalid method
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(?P<action>set|(ap|pre)pend|offsetSet)(?P<mode>File|Script)$/', $method, $matches)) {
            if (1 > count($args)) {
                throw new Exception(sprintf('Method "%s" requires at least one argument', $method));
            }

            $action  = $matches['action'];
            $mode    = strtolower($matches['mode']);
            $type    = 'text/javascript';
            $attrs   = array();

            if ('offsetSet' == $action) {
                $index = array_shift($args);
                if (1 > count($args)) {
                    throw new Exception(sprintf('Method "%s" requires at least two arguments, an index and source', $method));
                }
            }

            $content = $args[0];

            if (isset($args[1])) {
                $type = (string) $args[1];
            }
            if (isset($args[2])) {
                $attrs = (array) $args[2];
            }

            switch ($mode) {
                case 'script':
                    $item = $this->createData($type, $attrs, $content);
                    if ('offsetSet' == $action) {
                        $this->offsetSet($index, $item);
                    } else {
                        $this->$action($item);
                    }
                    break;
                case 'file':
                default:
                    if (!$this->_isDuplicate($content) || $action=='set') {
                        $attrs['src'] = $content;
                        $item = $this->createData($type, $attrs);
                        if ('offsetSet' == $action) {
                            $this->offsetSet($index, $item);
                        } else {
                            $this->$action($item);
                        }
                    }
                    break;
            }

            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Is the file specified a duplicate?
     *
     * @param  string $file
     * @return bool
     */
    protected function _isDuplicate($file)
    {
        foreach ($this->getContainer() as $item) {
            if (($item->source === null)
                && array_key_exists('src', $item->attributes)
                && ($file == $item->attributes['src']))
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Is the script provided valid?
     *
     * @param  mixed $value
     * @param  string $method
     * @return bool
     */
    protected function _isValid($value)
    {
        if ((!$value instanceof \stdClass)
            || !isset($value->type)
            || (!isset($value->source) && !isset($value->attributes)))
        {
            return false;
        }

        return true;
    }

    /**
     * Override append
     *
     * @param  string $value
     * @return void
     */
    public function append($value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid argument passed to append(); please use one of the helper methods, appendScript() or appendFile()');
        }

        return $this->getContainer()->append($value);
    }

    /**
     * Override prepend
     *
     * @param  string $value
     * @return void
     */
    public function prepend($value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid argument passed to prepend(); please use one of the helper methods, prependScript() or prependFile()');
        }

        return $this->getContainer()->prepend($value);
    }

    /**
     * Override set
     *
     * @param  string $value
     * @return void
     */
    public function set($value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid argument passed to set(); please use one of the helper methods, setScript() or setFile()');
        }

        return $this->getContainer()->set($value);
    }

    /**
     * Override offsetSet
     *
     * @param  string|int $index
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($index, $value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid argument passed to offsetSet(); please use one of the helper methods, offsetSetScript() or offsetSetFile()');
        }

        return $this->getContainer()->offsetSet($index, $value);
    }

    /**
     * Set flag indicating if arbitrary attributes are allowed
     *
     * @param  bool $flag
     * @return HeadScript
     */
    public function setAllowArbitraryAttributes($flag)
    {
        $this->_arbitraryAttributes = (bool) $flag;
        return $this;
    }

    /**
     * Are arbitrary attributes allowed?
     *
     * @return bool
     */
    public function arbitraryAttributesAllowed()
    {
        return $this->_arbitraryAttributes;
    }

    /**
     * Create script HTML
     *
     * @param  string $type
     * @param  array $attributes
     * @param  string $content
     * @param  string|int $indent
     * @return string
     */
    public function itemToString($item, $indent, $escapeStart, $escapeEnd)
    {
        $attrString = '';
        if (!empty($item->attributes)) {
            foreach ($item->attributes as $key => $value) {
                if ((!$this->arbitraryAttributesAllowed() && !in_array($key, $this->_optionalAttributes))
                    || in_array($key, array('conditional', 'noescape')))
                {
                    continue;
                }
                if ('defer' == $key) {
                    $value = 'defer';
                }
                $attrString .= sprintf(' %s="%s"', $key, ($this->_autoEscape) ? $this->_escape($value) : $value);
            }
        }

        $addScriptEscape = !(isset($item->attributes['noescape']) && filter_var($item->attributes['noescape'], FILTER_VALIDATE_BOOLEAN));

        $type = ($this->_autoEscape) ? $this->_escape($item->type) : $item->type;
        $html  = '<script type="' . $type . '"' . $attrString . '>';
        if (!empty($item->source)) {
            $html .= PHP_EOL ;

            if ($addScriptEscape) {
                $html .= $indent . '    ' . $escapeStart . PHP_EOL;
            }

            $html .= $indent . '    ' . $item->source;

            if ($addScriptEscape) {
                $html .= $indent . '    ' . $escapeEnd . PHP_EOL;
            }

            $html .= $indent;
        }
        $html .= '</script>';

        if (isset($item->attributes['conditional'])
            && !empty($item->attributes['conditional'])
            && is_string($item->attributes['conditional']))
        {
            // inner wrap with comment end and start if !IE
            if (str_replace(' ', '', $item->attributes['conditional']) === '!IE') {
                $html = '<!-->' . $html . '<!--';
            }
            $html = $indent . '<!--[if ' . $item->attributes['conditional'] . ']>' . $html . '<![endif]-->';
        } else {
            $html = $indent . $html;
        }

        return $html;
    }

    /**
     * Retrieve string representation
     *
     * @param  string|int $indent
     * @return string
     */
    public function toString($indent = null)
    {
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        if ($this->view) {
            $useCdata = $this->view->doctype()->isXhtml() ? true : false;
        } else {
            $useCdata = $this->useCdata ? true : false;
        }
        $escapeStart = ($useCdata) ? '//<![CDATA[' : '//<!--';
        $escapeEnd   = ($useCdata) ? '//]]>'       : '//-->';

        $items = array();
        $this->getContainer()->ksort();
        foreach ($this as $item) {
            if (!$this->_isValid($item)) {
                continue;
            }

            $items[] = $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
        }

        $return = implode($this->getSeparator(), $items);
        return $return;
    }

    /**
     *
     */
    protected function prepareEntries()
    {
        foreach ($this as &$item) {
            if (!$this->_isValid($item)) {
                continue;
            }

            if ($this->isCacheBuster()) {
                // adds the automatic cache buster functionality
                if (is_array($item->attributes)) {
                    if (isset($item->attributes["src"])) {
                        $realFile = PIMCORE_WEB_ROOT . $item->attributes["src"];
                        if (file_exists($realFile)) {
                            $item->attributes["src"] = "/cache-buster-" . filemtime($realFile) . $item->attributes["src"];
                        }
                    }
                }
            }

            \Pimcore::getEventManager()->trigger("frontend.view.helper.head-script", $this, [
                "item" => $item
            ]);
        }
    }

    /**
     * Create data item containing all necessary components of script
     *
     * @param  string $type
     * @param  array $attributes
     * @param  string $content
     * @return \stdClass
     */
    public function createData($type, array $attributes, $content = null)
    {
        $data             = new \stdClass();
        $data->type       = $type;
        $data->attributes = $attributes;
        $data->source     = $content;
        return $data;
    }
}
