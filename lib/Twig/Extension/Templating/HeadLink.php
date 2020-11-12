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

<<<<<<<< HEAD:lib/Twig/Extension/Templating/HeadLink.php
/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_View_Helper_HeadLink
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

use Pimcore\Event\FrontendEvents;
use Pimcore\Twig\Extension\Templating\Placeholder\CacheBusterAware;
use Pimcore\Twig\Extension\Templating\Placeholder\Container;
use Pimcore\Twig\Extension\Templating\Placeholder\ContainerService;
use Pimcore\Twig\Extension\Templating\Placeholder\Exception;
use Pimcore\Twig\Extension\Templating\Traits\WebLinksTrait;
use Symfony\Bridge\Twig\Extension\WebLinkExtension;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * HeadLink
 *
 * @see        http://www.w3.org/TR/xhtml1/dtds.html
 *
 * @method $this appendAlternate($href, $type, $title, $extras)
 * @method $this appendStylesheet($href, $media = 'screen', $conditionalStylesheet = false, array $extras = array())
 * @method $this offsetSetAlternate($index, $href, $type, $title, $extras)
 * @method $this offsetSetStylesheet($index, $href, $media = 'screen', $conditionalStylesheet = false, array $extras = array())
 * @method $this prependAlternate($href, $type, $title, $extras)
 * @method $this prependStylesheet($href, $media = 'screen', $conditionalStylesheet = false, array $extras = array())
 * @method $this setAlternate($href, $type, $title, $extras)
 * @method $this setStylesheet($href, $media = 'screen', $conditionalStylesheet = false, array $extras = array())
 *
 */
class HeadLink extends CacheBusterAware
{
    use WebLinksTrait;

    /**
     * $_validAttributes
     *
     * @var array
     */
    protected $_itemKeys = [
        'charset',
        'href',
        'hreflang',
        'id',
        'media',
        'rel',
        'rev',
        'type',
        'title',
        'extras',
        'sizes',
    ];

    /**
     * @var string registry key
     */
    protected $_regKey = 'HeadLink';

    /**
     * Default attributes for generated WebLinks (HTTP/2 push).
     *
     * @var array
     */
    protected $webLinkAttributes = ['as' => 'style'];

    /**
     * HeadLink constructor.
     *
     * Use PHP_EOL as separator
     *
     * @param ContainerService $containerService
     * @param WebLinkExtension $webLinkExtension
     */
    public function __construct(
        ContainerService $containerService,
        WebLinkExtension $webLinkExtension
    ) {
        parent::__construct($containerService);

        $this->webLinkExtension = $webLinkExtension;
        $this->setSeparator(PHP_EOL);
    }

    /**
     * headLink() - View Helper Method
     *
     * Returns current object instance. Optionally, allows passing array of
     * values to build link.
     *
     * @return HeadLink
     */
    public function __invoke(array $attributes = null, $placement = Container::APPEND)
    {
        if (null !== $attributes) {
            $item = $this->createData($attributes);
            switch ($placement) {
                case Container::SET:
                    $this->set($item);
                    break;
                case Container::PREPEND:
                    $this->prepend($item);
                    break;
                case Container::APPEND:
                default:
                    $this->append($item);
                    break;
            }
        }

        return $this;
    }

    /**
     * Overload method access
     *
     * Creates the following virtual methods:
     * - appendStylesheet($href, $media, $conditionalStylesheet, $extras)
     * - offsetSetStylesheet($index, $href, $media, $conditionalStylesheet, $extras)
     * - prependStylesheet($href, $media, $conditionalStylesheet, $extras)
     * - setStylesheet($href, $media, $conditionalStylesheet, $extras)
     * - appendAlternate($href, $type, $title, $extras)
     * - offsetSetAlternate($index, $href, $type, $title, $extras)
     * - prependAlternate($href, $type, $title, $extras)
     * - setAlternate($href, $type, $title, $extras)
     *
     * @param mixed $method
     * @param mixed $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(?P<action>set|(ap|pre)pend|offsetSet)(?P<type>Stylesheet|Alternate)$/', $method, $matches)) {
            $argc = count($args);
            $action = $matches['action'];
            $type = $matches['type'];
            $index = null;

            if ('offsetSet' == $action) {
                if (0 < $argc) {
                    $index = array_shift($args);
                    --$argc;
                }
            }

            if (1 > $argc) {
                throw new Exception(sprintf('%s requires at least one argument', $method));
            }

            if (is_array($args[0])) {
                $item = $this->createData($args[0]);
            } else {
                $dataMethod = 'createData' . $type;
                $item = $this->$dataMethod($args);
            }

            if ($item) {
                if ('offsetSet' == $action) {
                    $this->offsetSet($index, $item);
                } else {
                    $this->$action($item);
                }
            }

            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Check if value is valid
     *
     * @param  mixed $value
     *
     * @return bool
     */
    protected function _isValid($value)
    {
        if (!$value instanceof \stdClass) {
            return false;
        }

        $vars = get_object_vars($value);
        $keys = array_keys($vars);
        $intersection = array_intersect($this->_itemKeys, $keys);
        if (empty($intersection)) {
            return false;
        }

        return true;
    }

    /**
     * append()
     *
     * @param  array $value
     *
     * @return void
     */
    public function append($value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('append() expects a data token; please use one of the custom append*() methods');
        }

        $this->getContainer()->append($value);
    }

    /**
     * offsetSet()
     *
     * @param  string|int $index
     * @param  array $value
     *
     * @return void
     */
    public function offsetSet($index, $value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('offsetSet() expects a data token; please use one of the custom offsetSet*() methods');
        }

        $this->getContainer()->offsetSet($index, $value);
    }

    /**
     * prepend()
     *
     * @param array $value
     */
    public function prepend($value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('prepend() expects a data token; please use one of the custom prepend*() methods');
        }

        $this->getContainer()->prepend($value);
    }

    /**
     * set()
     *
     * @param array $value
     */
    public function set($value)
    {
        if (!$this->_isValid($value)) {
            throw new Exception('set() expects a data token; please use one of the custom set*() methods');
        }

        $this->getContainer()->set($value);
    }

    /**
     * Create HTML link element from data item
     *
     * @param  \stdClass $item
     *
     * @return string
     */
    public function itemToString(\stdClass $item)
    {
        $attributes = (array) $item;
        $link = '<link ';

        foreach ($this->_itemKeys as $itemKey) {
            if (isset($attributes[$itemKey])) {
                if (is_array($attributes[$itemKey])) {
                    foreach ($attributes[$itemKey] as $key => $value) {
                        $link .= sprintf('%s="%s" ', $key, ($this->_autoEscape) ? $this->_escape($value) : $value);
                    }
                } else {
                    $link .= sprintf('%s="%s" ', $itemKey, ($this->_autoEscape) ? $this->_escape($attributes[$itemKey]) : $attributes[$itemKey]);
                }
            }
        }

        $link .= '/>';

        if (($link == '<link />') || ($link == '<link >')) {
            return '';
        }

        if (isset($attributes['conditionalStylesheet'])
            && !empty($attributes['conditionalStylesheet'])
            && is_string($attributes['conditionalStylesheet'])) {
            if (str_replace(' ', '', $attributes['conditionalStylesheet']) === '!IE') {
                $link = '<!-->' . $link . '<!--';
            }
            $link = '<!--[if ' . $attributes['conditionalStylesheet'] . ']>' . $link . '<![endif]-->';
        }

        return $link;
    }

    /**
     * Render link elements as string
     *
     * @param  string|int $indent
     *
     * @return string
     */
    public function toString($indent = null)
    {
        $this->prepareEntries();

        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $items = [];
        $this->getContainer()->ksort();
        foreach ($this as $item) {
            $items[] = $this->itemToString($item);
        }

        return $indent . implode($this->_escape($this->getSeparator()) . $indent, $items);
    }

    /**
     * prepares entries with cache buster prefix
     */
    protected function prepareEntries()
    {
        foreach ($this as &$item) {
            if ($this->isCacheBuster()) {
                // adds the automatic cache buster functionality
                if (isset($item->href)) {
                    $realFile = PIMCORE_WEB_ROOT . $item->href;
                    if (file_exists($realFile)) {
                        $item->href = '/cache-buster-' . filemtime($realFile) . $item->href;
                    }
                }
            }

            $event = new GenericEvent($this, [
                'item' => $item,
            ]);
            \Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::VIEW_HELPER_HEAD_LINK);
========
namespace Pimcore\Templating\Helper;

@trigger_error(
    'Pimcore\Templating\Helper\HeadLink is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\HeadLink::class . ' instead.',
    E_USER_DEPRECATED
);
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/HeadLink.php

class_exists(\Pimcore\Twig\Extension\Templating\HeadLink::class);

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\HeadLink
     */
    class HeadLink extends \Pimcore\Twig\Extension\Templating\HeadLink {

    }
}
