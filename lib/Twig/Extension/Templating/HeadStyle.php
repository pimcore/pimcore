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

<<<<<<<< HEAD:lib/Twig/Extension/Templating/HeadStyle.php
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
     * @var string
     */
    protected $_regKey = 'HeadStyle';

    /**
     * Allowed optional attributes
     *
     * @var array
     */
    protected $_optionalAttributes = ['lang', 'title', 'media', 'dir'];

    /**
     * Allowed media types
     *
     * @var array
     */
    protected $_mediaTypes = [
        'all', 'aural', 'braille', 'handheld', 'print',
        'projection', 'screen', 'tty', 'tv',
    ];

    /**
     * Capture type and/or attributes (used for hinting during capture)
     *
     * @var string|null
     */
    protected $_captureAttrs = null;

    /**
     * Capture lock
     *
     * @var bool
     */
    protected $_captureLock;

    /**
     * Capture type (append, prepend, set)
     *
     * @var string
     */
    protected $_captureType;

    /**
     * HeadStyle constructor.
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
     * Return headStyle object
     *
     * Returns headStyle helper object; optionally, allows specifying
     *
     * @param  string $content Stylesheet contents
     * @param  string $placement Append, prepend, or set
     * @param  string|array $attributes Optional attributes to utilize
     *
     * @return HeadStyle
     */
    public function __invoke($content = null, $placement = 'APPEND', $attributes = [])
    {
        if ((null !== $content) && is_string($content)) {
            switch (strtoupper($placement)) {
                case 'SET':
                    $action = 'setStyle';
                    break;
                case 'PREPEND':
                    $action = 'prependStyle';
                    break;
                case 'APPEND':
                default:
                    $action = 'appendStyle';
                    break;
            }
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
     * @param  string $method
     * @param  array $args
     *
     * @return mixed
     *
     * @throws Exception When no $content provided or invalid method
     */
    public function __call($method, $args)
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

            $content = $args[0];
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
========
namespace Pimcore\Templating\Helper;

@trigger_error(
    'Pimcore\Templating\Helper\HeadStyle is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\HeadStyle::class . ' instead.',
    E_USER_DEPRECATED
);
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/HeadStyle.php

class_exists(\Pimcore\Twig\Extension\Templating\HeadStyle::class);

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\HeadStyle
     */
    class HeadStyle extends \Pimcore\Twig\Extension\Templating\HeadStyle {

    }
}
