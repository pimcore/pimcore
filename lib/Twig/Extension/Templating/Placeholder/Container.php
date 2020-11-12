<?php

declare(strict_types=1);

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

<<<<<<<< HEAD:lib/Twig/Extension/Templating/Placeholder/Container.php
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

class Container extends \ArrayObject
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
     * @var string
     */
    protected $_prefix = '';

    /**
     * What text to append the placeholder with when rendering
     *
     * @var string
     */
    protected $_postfix = '';

    /**
     * What string to use between individual items in the placeholder when rendering
     *
     * @var string
     */
    protected $_separator = '';

    /**
     * What string to use as the indentation of output, this will typically be spaces. Eg: '    '
     *
     * @var string
     */
    protected $_indent = '';

    /**
     * Whether or not we're already capturing for this given container
     *
     * @var bool
     */
    protected $_captureLock = false;

    /**
     * What type of capture (overwrite (set), append, prepend) to use
     *
     * @var string
     */
    protected $_captureType;

    /**
     * Key to which to capture content
     *
     * @var string
     */
    protected $_captureKey;

    /**
     * Set a single value
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function set($value)
    {
        $this->exchangeArray([$value]);
    }

    /**
     * Prepend a value to the top of the container
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function prepend($value)
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
     * @return mixed
     */
    public function getValue()
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
     * @param  string $prefix
     *
     * @return Container
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = (string) $prefix;

        return $this;
    }

    /**
     * Retrieve prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * Set postfix for __toString() serialization
     *
     * @param  string $postfix
     *
     * @return Container
     */
    public function setPostfix($postfix)
    {
        $this->_postfix = (string) $postfix;
========
namespace Pimcore\Templating\Helper\Placeholder;

@trigger_error(
    'Pimcore\Templating\Helper\Placeholder\Container is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\Placeholder\Container::class . ' instead.',
    E_USER_DEPRECATED
);
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/Placeholder/Container.php

class_exists(\Pimcore\Twig\Extension\Templating\Placeholder\Container::class);

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\Placeholder\Container
     */
    class Container extends \Pimcore\Twig\Extension\Templating\Placeholder\Container {

    }
}
