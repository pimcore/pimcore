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
 * based on @author ZF1 Zend_View_Helper_Placeholder_Container_Abstract
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

namespace Pimcore\Twig\Extension\Templating\Placeholder;

use ArrayObject;

class Container extends ArrayObject
{
    /**
     * Whether or not to override all contents of placeholder
     *
     * @const string
     */
    const SET = 'SET';

    /**
     * Whether or not to append contents to placeholder
     *
     * @const string
     */
    const APPEND = 'APPEND';

    /**
     * Whether or not to prepend contents to placeholder
     *
     * @const string
     */
    const PREPEND = 'PREPEND';

    /**
     * What text to prefix the placeholder with when rendering
     *
     */
    protected string $_prefix = '';

    /**
     * What text to append the placeholder with when rendering
     *
     */
    protected string $_postfix = '';

    /**
     * What string to use between individual items in the placeholder when rendering
     *
     */
    protected string $_separator = '';

    /**
     * What string to use as the indentation of output, this will typically be spaces. Eg: '    '
     *
     */
    protected string $_indent = '';

    /**
     * Whether or not we're already capturing for this given container
     *
     */
    protected bool $_captureLock = false;

    /**
     * What type of capture (overwrite (set), append, prepend) to use
     *
     */
    protected string $_captureType;

    /**
     * Key to which to capture content
     *
     */
    protected ?string $_captureKey = null;

    /**
     * Set a single value
     *
     *
     */
    public function set(mixed $value): void
    {
        $this->exchangeArray([$value]);
    }

    /**
     * Prepend a value to the top of the container
     *
     *
     */
    public function prepend(mixed $value): void
    {
        $values = $this->getArrayCopy();
        array_unshift($values, $value);
        $this->exchangeArray($values);
    }

    /**
     * Retrieve container value
     *
     * If single element registered, returns that element; otherwise,
     * serializes to array.
     *
     */
    public function getValue(): mixed
    {
        if (1 == count($this)) {
            $keys = $this->getKeys();
            $key = array_shift($keys);

            return $this[$key];
        }

        return $this->getArrayCopy();
    }

    /**
     * Set prefix for __toString() serialization
     *
     *
     * @return $this
     */
    public function setPrefix(string $prefix): static
    {
        $this->_prefix = $prefix;

        return $this;
    }

    /**
     * Retrieve prefix
     *
     */
    public function getPrefix(): string
    {
        return $this->_prefix;
    }

    /**
     * Set postfix for __toString() serialization
     *
     *
     * @return $this
     */
    public function setPostfix(string $postfix): static
    {
        $this->_postfix = $postfix;

        return $this;
    }

    /**
     * Retrieve postfix
     *
     */
    public function getPostfix(): string
    {
        return $this->_postfix;
    }

    /**
     * Set separator for __toString() serialization
     *
     * Used to implode elements in container
     *
     *
     * @return $this
     */
    public function setSeparator(string $separator): static
    {
        $this->_separator = $separator;

        return $this;
    }

    /**
     * Retrieve separator
     *
     */
    public function getSeparator(): string
    {
        return $this->_separator;
    }

    /**
     * Set the indentation string for __toString() serialization,
     * optionally, if a number is passed, it will be the number of spaces
     *
     *
     * @return $this
     */
    public function setIndent(int|string $indent): static
    {
        $this->_indent = $this->getWhitespace($indent);

        return $this;
    }

    /**
     * Retrieve indentation
     *
     */
    public function getIndent(): string
    {
        return $this->_indent;
    }

    /**
     * Retrieve whitespace representation of $indent
     *
     *
     */
    public function getWhitespace(int|string $indent): string
    {
        if (is_int($indent)) {
            $indent = str_repeat(' ', $indent);
        }

        return (string) $indent;
    }

    /**
     * Start capturing content to push into placeholder
     *
     * @param int|string $type How to capture content into placeholder; append, prepend, or set
     *
     * @throws Exception
     *
     * @deprecated Use twig set tag for output capturing instead.
     */
    public function captureStart(int|string $type = self::APPEND, mixed $key = null): void
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.4',
            'Using "captureStart()" is deprecated. Use twig set tag for output capturing instead.'
        );

        if ($this->_captureLock) {
            throw new Exception('Cannot nest placeholder captures for the same placeholder');
        }

        $this->_captureLock = true;
        $this->_captureType = $type;
        if ((null !== $key) && is_scalar($key)) {
            $this->_captureKey = (string) $key;
        }
        ob_start();
    }

    /**
     * End content capture
     *
     * @deprecated Use twig set tag for output capturing instead.
     */
    public function captureEnd(): void
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.4',
            'Using "captureStart()" is deprecated. Use twig set tag for output capturing instead.'
        );

        $data = ob_get_clean();
        $key = null;
        $this->_captureLock = false;
        if (null !== $this->_captureKey) {
            $key = $this->_captureKey;
        }
        switch ($this->_captureType) {
            case self::SET:
                if (null !== $key) {
                    $this[$key] = $data;
                } else {
                    $this->exchangeArray([$data]);
                }

                break;
            case self::PREPEND:
                if (null !== $key) {
                    $array = [$key => $data];
                    $values = $this->getArrayCopy();
                    $final = $array + $values;
                    $this->exchangeArray($final);
                } else {
                    $this->prepend($data);
                }

                break;
            case self::APPEND:
            default:
                if (null !== $key) {
                    if (empty($this[$key])) {
                        $this[$key] = $data;
                    } else {
                        $this[$key] .= $data;
                    }
                } else {
                    $this[$this->nextIndex()] = $data;
                }

                break;
        }
    }

    /**
     * Get keys
     *
     */
    public function getKeys(): array
    {
        $array = $this->getArrayCopy();

        return array_keys($array);
    }

    /**
     * Next Index
     *
     * as defined by the PHP manual
     *
     */
    public function nextIndex(): int
    {
        $keys = $this->getKeys();
        if (0 == count($keys)) {
            return 0;
        }

        return $nextIndex = max($keys) + 1;
    }

    /**
     * Render the placeholder
     *
     *
     */
    public function toString(int|string $indent = null): string
    {
        // Check items
        if (0 === $this->count()) {
            return '';
        }

        $indent = ($indent !== null)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $items = $this->getArrayCopy();
        $return = $indent
            . $this->getPrefix()
            . implode($this->getSeparator(), $items)
            . $this->getPostfix();
        $return = preg_replace("/(\r\n?|\n)/", '$1' . $indent, $return);

        return $return;
    }

    /**
     * Serialize object to string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
