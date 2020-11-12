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

<<<<<<<< HEAD:lib/Twig/Extension/Templating/HeadMeta.php
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
     * @var array
     */
    protected $_typeKeys = ['name', 'http-equiv', 'charset', 'property'];
    protected $_requiredKeys = ['content'];
    protected $_modifierKeys = ['lang', 'scheme'];
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
     * Retrieve object instance; optionally add meta tag
     *
     * @param  string $content
     * @param  string $keyValue
     * @param  string $keyType
     * @param  array $modifiers
     * @param  string $placement
     *
     * @return HeadMeta
     */
    public function __invoke($content = null, $keyValue = null, $keyType = 'name', $modifiers = [], $placement = Container::APPEND)
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
     * @param string $type
     * @param string $keyValue
     *
     * @return mixed
     */
    public function getItem($type, $keyValue)
    {
        foreach ($this->getContainer() as $item) {
            if (isset($item->$type) && $item->$type == $keyValue) {
                return $item->content;
            }
        }
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
     * @param  string $method
     * @param  array $args
     *
     * @return HeadMeta
     */
    public function __call($method, $args)
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
     * @param  mixed $item
     *
     * @return bool
     */
    protected function _isValid($item)
    {
        return true;
    }
========
namespace Pimcore\Templating\Helper;

@trigger_error(
    'Pimcore\Templating\Helper\HeadMeta is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\HeadMeta::class . ' instead.',
    E_USER_DEPRECATED
);
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/HeadMeta.php

class_exists(\Pimcore\Twig\Extension\Templating\HeadMeta::class);

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\HeadMeta
     */
    class HeadMeta extends \Pimcore\Twig\Extension\Templating\HeadMeta {

    }
}
