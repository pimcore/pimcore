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
 * based on @author ZF1 Zend_View_Helper_HeadMeta
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

use Pimcore\Bundle\PimcoreBundle\Templating\Helper\Placeholder\AbstractHelper;
use Pimcore\Bundle\PimcoreBundle\Templating\Helper\Placeholder\Container;
use Pimcore\Bundle\PimcoreBundle\Templating\Helper\Placeholder\ContainerService;

/**
 * Zend_Layout_View_Helper_HeadMeta
 *
 * @see        http://www.w3.org/TR/xhtml1/dtds.html
 * @uses       Zend_View_Helper_Placeholder_Container_Standalone
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @method $this appendHttpEquiv($keyValue, $content, $conditionalHttpEquiv)
 * @method $this appendName($keyValue, $content, $conditionalName)
 * @method $this appendProperty($property, $content, $modifiers)
 * @method $this offsetSetHttpEquiv($index, $keyValue, $content, $conditionalHttpEquiv)
 * @method $this offsetSetName($index, $keyValue, $content, $conditionalName)
 * @method $this offsetSetProperty($index, $property, $content, $modifiers)
 * @method $this prependHttpEquiv($keyValue, $content, $conditionalHttpEquiv)
 * @method $this prependName($keyValue, $content, $conditionalName)
 * @method $this prependProperty($property, $content, $modifiers)
 * @method $this setHttpEquiv($keyValue, $content, $modifiers)
 * @method $this setName($keyValue, $content, $modifiers)
 * @method $this setProperty($property, $content, $modifiers)
 */
class HeadMeta extends AbstractHelper
{
    /**
     * Types of attributes
     * @var array
     */
    protected $_typeKeys     = array('name', 'http-equiv', 'charset', 'property');
    protected $_requiredKeys = array('content');
    protected $_modifierKeys = array('lang', 'scheme');
    protected $rawItems = [];

    /**
     * @var string registry key
     */
    protected $_regKey = 'HeadMeta';

    /**
     * HeadMeta constructor.
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
        return 'headMeta';
    }

    /**
     * Retrieve object instance; optionally add meta tag
     *
     * @param  string $content
     * @param  string $keyValue
     * @param  string $keyType
     * @param  array $modifiers
     * @param  string $placement
     * @return HeadMeta
     */
    public function __invoke($content = null, $keyValue = null, $keyType = 'name', $modifiers = array(), $placement = Container::APPEND)
    {
        if ((null !== $content) && (null !== $keyValue)) {
            $item   = $this->createData($keyType, $keyValue, $content, $modifiers);
            $action = strtolower($placement);
            switch ($action) {
                case 'append':
                case 'prepend':
                case 'set':
                    $this->$action($item);
                    break;
                default:
                    $this->append($item);
                    break;
            }
        }

        return $this;
    }

    protected function _normalizeType($type)
    {
        switch ($type) {
            case 'Name':
                return 'name';
            case 'HttpEquiv':
                return 'http-equiv';
            case 'Property':
                return 'property';
            default:
                throw new Exception(sprintf('Invalid type "%s" passed to _normalizeType', $type));
        }
    }

    /**
     * Overload method access
     *
     * Allows the following 'virtual' methods:
     * - appendName($keyValue, $content, $modifiers = array())
     * - offsetGetName($index, $keyValue, $content, $modifers = array())
     * - prependName($keyValue, $content, $modifiers = array())
     * - setName($keyValue, $content, $modifiers = array())
     * - appendHttpEquiv($keyValue, $content, $modifiers = array())
     * - offsetGetHttpEquiv($index, $keyValue, $content, $modifers = array())
     * - prependHttpEquiv($keyValue, $content, $modifiers = array())
     * - setHttpEquiv($keyValue, $content, $modifiers = array())
     * - appendProperty($keyValue, $content, $modifiers = array())
     * - offsetGetProperty($index, $keyValue, $content, $modifiers = array())
     * - prependProperty($keyValue, $content, $modifiers = array())
     * - setProperty($keyValue, $content, $modifiers = array())
     *
     * @param  string $method
     * @param  array $args
     * @return HeadMeta
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(?P<action>set|(pre|ap)pend|offsetSet)(?P<type>Name|HttpEquiv|Property)$/', $method, $matches)) {
            $action = $matches['action'];
            $type   = $this->_normalizeType($matches['type']);
            $argc   = count($args);
            $index  = null;

            if ('offsetSet' == $action) {
                if (0 < $argc) {
                    $index = array_shift($args);
                    --$argc;
                }
            }

            if (2 > $argc) {
                throw new Exception('Too few arguments provided; requires key value, and content');
            }

            if (3 > $argc) {
                $args[] = array();
            }

            $item  = $this->createData($type, $args[0], $args[1], $args[2]);

            if ('offsetSet' == $action) {
                return $this->offsetSet($index, $item);
            }

            $this->$action($item);
            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Determine if item is valid
     *
     * @param  mixed $item
     * @return boolean
     */
    protected function _isValid($item)
    {
        return true;
    }

    /**
     * Append
     *
     * @param  string $value
     * @return void
     * @throws Exception
     */
    public function append($value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid value passed to append; please use appendMeta()');
        }

        return $this->getContainer()->append($value);
    }

    /**
     * OffsetSet
     *
     * @param  string|int $index
     * @param  string $value
     * @return void
     * @throws Exception
     */
    public function offsetSet($index, $value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid value passed to offsetSet; please use offsetSetName() or offsetSetHttpEquiv()');
        }

        return $this->getContainer()->offsetSet($index, $value);
    }

    /**
     * OffsetUnset
     *
     * @param  string|int $index
     * @return void
     * @throws Exception
     */
    public function offsetUnset($index)
    {
        if (!in_array($index, $this->getContainer()->getKeys())) {
            throw new Exception('Invalid index passed to offsetUnset()');
        }

        return $this->getContainer()->offsetUnset($index);
    }

    /**
     * Prepend
     *
     * @param  string $value
     * @return void
     * @throws Exception
     */
    public function prepend($value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid value passed to prepend; please use prependMeta()');
        }

        return $this->getContainer()->prepend($value);
    }

    /**
     * Set
     *
     * @param  string $value
     * @return void
     * @throws Exception
     */
    public function set($value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid value passed to set; please use setMeta()');
        }

        $container = $this->getContainer();
        foreach ($container->getArrayCopy() as $index => $item) {
            if ($item->type == $value->type && $item->{$item->type} == $value->{$value->type}) {
                $this->offsetUnset($index);
            }
        }

        return $this->append($value);
    }

    /**
     * Build meta HTML string
     *
     * @param  string $type
     * @param  string $typeValue
     * @param  string $content
     * @param  array $modifiers
     * @return string
     */
    public function itemToString(\stdClass $item)
    {
        if (!in_array($item->type, $this->_typeKeys)) {
            throw new Exception(sprintf('Invalid type "%s" provided for meta', $item->type));
        }
        $type = $item->type;

        $modifiersString = '';
        foreach ($item->modifiers as $key => $value) {
            if (!is_null($this->view) && $this->view->doctype()->isHtml5()
                && $key == 'scheme') {
                throw new Exception('Invalid modifier '
                    . '"scheme" provided; not supported by HTML5');
            }
            if (!in_array($key, $this->_modifierKeys)) {
                continue;
            }
            $modifiersString .= $key . '="' . $this->_escape($value) . '" ';
        }

        $tpl = '<meta %s="%s" content="%s" %s/>';

        $meta = sprintf(
            $tpl,
            $type,
            $this->_escape($item->$type),
            $this->_escape($item->content),
            $modifiersString
        );

        if (isset($item->modifiers['conditional'])
            && !empty($item->modifiers['conditional'])
            && is_string($item->modifiers['conditional']))
        {
            if (str_replace(' ', '', $item->modifiers['conditional']) === '!IE') {
                $meta = '<!-->' . $meta . '<!--';
            }
            $meta = '<!--[if ' . $this->_escape($item->modifiers['conditional']) . ']>' . $meta . '<![endif]-->';
        }

        return $meta;
    }

    /**
     * Render placeholder as string
     *
     * @param  string|int $indent
     * @return string
     */
    public function toString($indent = null)
    {
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $items = array();
        $this->getContainer()->ksort();
        try {
            foreach ($this as $item) {
                $items[] = $this->itemToString($item);
            }
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
        $metaString = $indent . implode($this->_escape($this->getSeparator()) . $indent, $items);

        // add raw items
        $separator = $this->_escape($this->getSeparator()) . $indent;
        $metaString .= ($separator . implode($separator, $this->rawItems));

        return $metaString;
    }

    /**
     * Create data item for inserting into stack
     *
     * @param  string $type
     * @param  string $typeValue
     * @param  string $content
     * @param  array $modifiers
     * @return \stdClass
     */
    public function createData($type, $typeValue, $content, array $modifiers)
    {
        $data            = new \stdClass;
        $data->type      = $type;
        $data->$type     = $typeValue;
        $data->content   = $content;
        $data->modifiers = $modifiers;
        return $data;
    }


    /**
     * @param string $html
     * @return $this
     */
    public function addRaw($html)
    {
        $this->rawItems[] = $html;

        return $this;
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        return $this->rawItems;
    }

}
