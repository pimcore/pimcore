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
 * based on @author ZF1 Zend_View_Helper_HeadStyle
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
use stdClass;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @method $this appendStyle($content, array $attributes = array())
 * @method $this offsetSetStyle($index, $content, array $attributes = array())
 * @method $this prependStyle($content, array $attributes = array())
 * @method $this setStyle($content, array $attributes = array())
 *
 */
class HeadStyle extends AbstractExtension implements RuntimeExtensionInterface
{
    /**
     * Registry key for placeholder
     *
     */
    protected string $_regKey = 'HeadStyle';

    /**
     * Allowed optional attributes
     *
     */
    protected array $_optionalAttributes = ['lang', 'title', 'media', 'dir'];

    /**
     * Allowed media types
     *
     */
    protected array $_mediaTypes = [
        'all', 'aural', 'braille', 'handheld', 'print',
        'projection', 'screen', 'tty', 'tv',
    ];

    /**
     * Capture type and/or attributes (used for hinting during capture)
     *
     */
    protected ?array $_captureAttrs = null;

    /**
     * Capture lock
     *
     */
    protected bool $_captureLock = false;

    /**
     * Capture type (append, prepend, set)
     *
     */
    protected string $_captureType;

    /**
     * HeadStyle constructor.
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
     * Return headStyle object
     *
     * Returns headStyle helper object; optionally, allows specifying
     *
     * @param string|null $content Stylesheet contents
     * @param string $placement Append, prepend, or set
     * @param array|string $attributes Optional attributes to utilize
     *
     * @return $this
     */
    public function __invoke(string $content = null, string $placement = 'APPEND', array|string $attributes = []): static
    {
        if (is_string($content)) {
            $action = match (strtoupper($placement)) {
                'SET' => 'setStyle',
                'PREPEND' => 'prependStyle',
                default => 'appendStyle',
            };
            $this->$action($content, $attributes);
        }

        return $this;
    }

    /**
     * Overload method calls
     *
     * Allows the following method calls:
     * - appendStyle($content, $attributes = array())
     * - offsetSetStyle($index, $content, $attributes = array())
     * - prependStyle($content, $attributes = array())
     * - setStyle($content, $attributes = array())
     *
     *
     *
     * @throws Exception When no $content provided or invalid method
     */
    public function __call(string $method, array $args): mixed
    {
        if (preg_match('/^(?P<action>set|(ap|pre)pend|offsetSet)(Style)$/', $method, $matches)) {
            $index = null;
            $argc = count($args);
            $action = $matches['action'];

            if ('offsetSet' == $action) {
                if (0 < $argc) {
                    $index = array_shift($args);
                    --$argc;
                }
            }

            if (1 > $argc) {
                throw new Exception(sprintf('Method "%s" requires minimally content for the stylesheet', $method));
            }

            $content = (string)$args[0];
            $attrs = [];
            if (isset($args[1])) {
                $attrs = (array) $args[1];
            }

            $item = $this->createData($content, $attrs);

            if ('offsetSet' == $action) {
                $this->offsetSet($index, $item);
            } else {
                $this->$action($item);
            }

            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Determine if a value is a valid style tag
     *
     *
     */
    protected function _isValid(mixed $value): bool
    {
        if ((!$value instanceof stdClass)
            || !isset($value->content)
            || !isset($value->attributes)) {
            return false;
        }

        return true;
    }

    /**
     * Override append to enforce style creation
     *
     *
     */
    public function append(mixed $value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid value passed to append; please use appendStyle()');
        }

        $this->getContainer()->append($value);
    }

    /**
     * Override offsetSet to enforce style creation
     *
     * @param  string|int $offset
     *
     */
    public function offsetSet($offset, mixed $value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid value passed to offsetSet; please use offsetSetStyle()');
        }

        $this->getContainer()->offsetSet($offset, $value);
    }

    /**
     * Override prepend to enforce style creation
     *
     *
     */
    public function prepend(mixed $value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid value passed to prepend; please use prependStyle()');
        }

        $this->getContainer()->prepend($value);
    }

    /**
     * Override set to enforce style creation
     *
     *
     */
    public function set(mixed $value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid value passed to set; please use setStyle()');
        }

        $this->getContainer()->set($value);
    }

    /**
     * Start capture action
     *
     * @param string $type
     * @param array|null $attrs
     *
     * @deprecated Use twig set tag for output capturing instead.
     */
    public function captureStart($type = Container::APPEND, $attrs = null): void
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.4',
            'Using "captureStart()" is deprecated. Use twig set tag for output capturing instead.'
        );

        if ($this->_captureLock) {
            throw new Exception('Cannot nest headStyle captures');
        }

        $this->_captureLock = true;
        $this->_captureAttrs = $attrs;
        $this->_captureType = $type;
        ob_start();
    }

    /**
     * End capture action and store
     *
     * @deprecated Use twig set tag for output capturing instead.
     */
    public function captureEnd(): void
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.4',
            'Using "captureEnd()" is deprecated. Use twig set tag for output capturing instead.'
        );

        $content = ob_get_clean();
        $attrs = $this->_captureAttrs;
        $this->_captureAttrs = null;
        $this->_captureLock = false;

        switch ($this->_captureType) {
            case Container::SET:
                $this->setStyle($content, $attrs);

                break;
            case Container::PREPEND:
                $this->prependStyle($content, $attrs);

                break;
            case Container::APPEND:
            default:
                $this->appendStyle($content, $attrs);

                break;
        }
    }

    /**
     * Convert content and attributes into valid style tag
     *
     * @param  stdClass $item Item to render
     * @param string|null $indent Indentation to use
     *
     */
    public function itemToString(stdClass $item, ?string $indent): string
    {
        $attrString = '';
        if (!empty($item->attributes)) {
            foreach ($item->attributes as $key => $value) {
                if (!in_array($key, $this->_optionalAttributes)) {
                    continue;
                }
                if ('media' == $key) {
                    if (!str_contains($value, ',')) {
                        if (!in_array($value, $this->_mediaTypes)) {
                            continue;
                        }
                    } else {
                        $media_types = explode(',', $value);
                        $value = '';
                        foreach ($media_types as $type) {
                            $type = trim($type);
                            if (!in_array($type, $this->_mediaTypes)) {
                                continue;
                            }
                            $value .= $type .',';
                        }
                        $value = substr($value, 0, -1);
                    }
                }
                $attrString .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
            }
        }

        $escapeStart = $indent . '<!--'. PHP_EOL;
        $escapeEnd = $indent . '-->'. PHP_EOL;
        if (isset($item->attributes['conditional'])
            && !empty($item->attributes['conditional'])
            && is_string($item->attributes['conditional'])
        ) {
            $escapeStart = null;
            $escapeEnd = null;
        }

        $html = '<style' . $attrString . '>' . PHP_EOL
            . $escapeStart . $indent . $item->content . PHP_EOL . $escapeEnd
            . '</style>';

        if (null === $escapeStart) {
            if (str_replace(' ', '', $item->attributes['conditional']) === '!IE') {
                $html = '<!-->' . $html . '<!--';
            }
            $html = '<!--[if ' . $item->attributes['conditional'] . ']>' . $html . '<![endif]-->';
        }

        return $html;
    }

    /**
     * Create string representation of placeholder
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
        foreach ($this as $item) {
            if (!$this->_isValid($item)) {
                continue;
            }
            $items[] = $this->itemToString($item, $indent);
        }

        $return = $indent . implode($this->getSeparator() . $indent, $items);
        $return = preg_replace("/(\r\n?|\n)/", '$1' . $indent, $return);

        return $return;
    }

    /**
     * Create data item for use in stack
     *
     *
     */
    public function createData(string $content, array $attributes): stdClass
    {
        if (!isset($attributes['media'])) {
            $attributes['media'] = 'screen';
        } elseif (is_array($attributes['media'])) {
            $attributes['media'] = implode(',', $attributes['media']);
        }

        $data = new stdClass();
        $data->content = $content;
        $data->attributes = $attributes;

        return $data;
    }
}
