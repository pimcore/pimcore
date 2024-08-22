<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Twig\Extension\Templating;

use Pimcore\Twig\Extension\Templating\Placeholder\AbstractExtension;
use Pimcore\Twig\Extension\Templating\Placeholder\Container;
use Pimcore\Twig\Extension\Templating\Placeholder\ContainerService;
use Pimcore\Twig\Extension\Templating\Placeholder\Exception;
use Pimcore\Twig\Extension\Templating\Traits\TextUtilsTrait;
use stdClass;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @method $this appendHttpEquiv($keyValue, $content, $conditionalHttpEquiv=[])
 * @method $this appendName($keyValue, $content, $conditionalName=[])
 * @method $this appendProperty($property, $content, $modifiers=[])
 * @method $this offsetSetHttpEquiv($index, $keyValue, $content, $conditionalHttpEquiv=[])
 * @method $this offsetSetName($index, $keyValue, $content, $conditionalName=[])
 * @method $this offsetSetProperty($index, $property, $content, $modifiers=[])
 * @method $this prependHttpEquiv($keyValue, $content, $conditionalHttpEquiv=[])
 * @method $this prependName($keyValue, $content, $conditionalName=[])
 * @method $this prependProperty($property, $content, $modifiers=[])
 * @method $this setHttpEquiv($keyValue, $content, $modifiers=[])
 * @method $this setName($keyValue, $content, $modifiers=[])
 * @method $this setProperty($property, $content, $modifiers=[])
 *
 */
class HeadMeta extends AbstractExtension implements RuntimeExtensionInterface
{
    use TextUtilsTrait;

    /**
     * Types of attributes
     *
     */
    protected array $_typeKeys = ['name', 'http-equiv', 'charset', 'property'];

    protected array $_requiredKeys = ['content'];

    protected array $_modifierKeys = ['lang', 'scheme'];

    protected array $rawItems = [];

    /**
     * @var string registry key
     */
    protected string $_regKey = 'HeadMeta';

    /**
     * HeadMeta constructor.
     *
     * Set separator to PHP_EOL.
     *
     */
    public function __construct(ContainerService $containerService)
    {
        parent::__construct($containerService);
        $this->setSeparator(PHP_EOL);
    }

    /**
     * Retrieve object instance; optionally add meta tag
     *
     *
     * @return $this
     */
    public function __invoke(string $content = null, string $keyValue = null, string $keyType = 'name', array $modifiers = [], string $placement = Container::APPEND): static
    {
        if ((null !== $content) && (null !== $keyValue)) {
            $item = $this->createData($keyType, $keyValue, $content, $modifiers);
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

    protected function _normalizeType(string $type): string
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

    public function getItem(string $type, string $keyValue): mixed
    {
        foreach ($this->getContainer() as $item) {
            if (isset($item->$type) && $item->$type == $keyValue) {
                return $item->content;
            }
        }

        return null;
    }

    /**
     * Overload method access
     *
     * Allows the following 'virtual' methods:
     * - appendName($keyValue, $content, $modifiers = array())
     * - prependName($keyValue, $content, $modifiers = array())
     * - setName($keyValue, $content, $modifiers = array())
     * - appendHttpEquiv($keyValue, $content, $modifiers = array())
     * - prependHttpEquiv($keyValue, $content, $modifiers = array())
     * - setHttpEquiv($keyValue, $content, $modifiers = array())
     * - appendProperty($keyValue, $content, $modifiers = array())
     * - prependProperty($keyValue, $content, $modifiers = array())
     * - setProperty($keyValue, $content, $modifiers = array())
     *
     *
     * @return HeadMeta
     */
    public function __call(string $method, array $args): mixed
    {
        if (preg_match('/^(?P<action>set|(pre|ap)pend|offsetSet)(?P<type>Name|HttpEquiv|Property)$/', $method, $matches)) {
            $action = $matches['action'];
            $type = $this->_normalizeType($matches['type']);
            $argc = count($args);
            $index = null;

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
                $args[] = [];
            }

            $item = $this->createData($type, $args[0], $args[1], $args[2]);

            if ('offsetSet' == $action) {
                $this->offsetSet($index, $item);

                return $this;
            }

            $this->$action($item);

            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Determine if item is valid
     *
     *
     */
    protected function _isValid(mixed $item): bool
    {
        return true;
    }

    /**
     * Append
     *
     * @param  stdClass $value
     *
     * @throws Exception
     */
    public function append($value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid value passed to append; please use appendMeta()');
        }

        $this->getContainer()->append($value);
    }

    /**
     * OffsetSet
     *
     * @param  string|int $offset
     *
     * @throws Exception
     */
    public function offsetSet($offset, mixed $value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid value passed to offsetSet; please use offsetSetName() or offsetSetHttpEquiv()');
        }

        $this->getContainer()->offsetSet($offset, $value);
    }

    /**
     * OffsetUnset
     *
     * @param  string|int $index
     *
     * @throws Exception
     */
    public function offsetUnset($index): void
    {
        if (!in_array($index, $this->getContainer()->getKeys())) {
            throw new Exception('Invalid index passed to offsetUnset()');
        }

        $this->getContainer()->offsetUnset($index);
    }

    /**
     * Prepend
     *
     * @param  string $value
     *
     * @throws Exception
     */
    public function prepend($value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid value passed to prepend; please use prependMeta()');
        }

        $this->getContainer()->prepend($value);
    }

    /**
     * Set
     *
     *
     *
     * @throws Exception
     */
    public function set(mixed $value): void
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

        $this->append($value);
    }

    /**
     * Build meta HTML string
     *
     *
     */
    public function itemToString(stdClass $item): string
    {
        if (!in_array($item->type, $this->_typeKeys)) {
            throw new Exception(sprintf('Invalid type "%s" provided for meta', $item->type));
        }
        $type = $item->type;

        $modifiersString = '';
        foreach ($item->modifiers as $key => $value) {
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
            && is_string($item->modifiers['conditional'])) {
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
     *
     */
    public function toString(int|string $indent = null): string
    {
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $items = [];
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
     *
     */
    public function createData(string $type, string $typeValue, string $content, array $modifiers): stdClass
    {
        $data = new stdClass;
        $data->type = $type;
        $data->$type = $typeValue;
        $data->content = $content;
        $data->modifiers = $modifiers;

        return $data;
    }

    public function addRaw(string $html): static
    {
        $this->rawItems[] = $html;

        return $this;
    }

    public function getRaw(): array
    {
        return $this->rawItems;
    }

    /**
     *
     * @return $this
     */
    public function setDescription(string $string, int $length = null, string $suffix = ''): static
    {
        $string = $this->normalizeString($string, $length, $suffix);

        return $this->setName('description', $string);
    }
}
