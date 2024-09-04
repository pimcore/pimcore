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
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Twig\Extension\Templating;

use Pimcore;
use Pimcore\Event\FrontendEvents;
use Pimcore\Twig\Extension\Templating\Placeholder\CacheBusterAware;
use Pimcore\Twig\Extension\Templating\Placeholder\Container;
use Pimcore\Twig\Extension\Templating\Placeholder\ContainerService;
use Pimcore\Twig\Extension\Templating\Placeholder\Exception;
use Pimcore\Twig\Extension\Templating\Traits\WebLinksTrait;
use stdClass;
use Symfony\Bridge\Twig\Extension\WebLinkExtension;
use Symfony\Component\EventDispatcher\GenericEvent;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @method $this appendFile($src, $type = 'text/javascript', array $attrs = array())
 * @method $this appendScript($script, $type = 'text/javascript', array $attrs = array())
 * @method $this offsetSetFile($index, $src, $type = 'text/javascript', array $attrs = array())
 * @method $this offsetSetScript($index, $script, $type = 'text/javascript', array $attrs = array())
 * @method $this prependFile($src, $type = 'text/javascript', array $attrs = array())
 * @method $this prependScript($script, $type = 'text/javascript', array $attrs = array())
 * @method $this setFile($src, $type = 'text/javascript', array $attrs = array())
 * @method $this setScript($script, $type = 'text/javascript', array $attrs = array())
 *
 */
class HeadScript extends CacheBusterAware implements RuntimeExtensionInterface
{
    use WebLinksTrait;

    /**#@+
     * Script type contants
     * @const string
     */
    const FILE = 'FILE';

    const SCRIPT = 'SCRIPT';

    // #@-

    /**
     * Registry key for placeholder
     *
     */
    protected string $_regKey = 'HeadScript';

    /**
     * Are arbitrary attributes allowed?
     *
     */
    protected bool $_arbitraryAttributes = false;

    /**#@+
     * Capture type and/or attributes (used for hinting during capture)
     * @var bool
     */
    protected bool $_captureLock = false;

    protected ?string $_captureScriptType = null;

    protected ?array $_captureScriptAttrs = null;

    protected string $_captureType;

    // #@-

    /**
     * Optional allowed attributes for script tag
     *
     */
    protected array $_optionalAttributes = [
        'charset', 'defer', 'language', 'src', 'type', 'async',
    ];

    /**
     * Required attributes for script tag
     *
     */
    protected array $_requiredAttributes = ['type'];

    /**
     * Whether or not to format scripts using CDATA; used only if doctype
     * helper is not accessible
     *
     */
    public bool $useCdata = false;

    /**
     * Default attributes for generated WebLinks (HTTP/2 push).
     *
     * @var array
     */
    protected $webLinkAttributes = ['as' => 'script'];

    /**
     * HeadScript constructor.
     *
     * Set separator to PHP_EOL.
     *
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
     * Return headScript object
     *
     * Returns headScript helper object; optionally, allows specifying a script
     * or script file to include.
     *
     * @param string $mode Script or file
     * @param string|null $spec Script/url
     * @param string $placement Append, prepend, or set
     * @param  array $attrs Array of script attributes
     * @param string $type Script type and/or array of script attributes
     *
     * @return $this
     */
    public function __invoke(string $mode = self::FILE, string $spec = null, string $placement = 'APPEND', array $attrs = [], string $type = 'text/javascript'): static
    {
        if (is_string($spec)) {
            $action = ucfirst(strtolower($mode));
            $placement = strtolower($placement);
            $action = match ($placement) {
                'set', 'prepend', 'append' => $placement . $action,
                default => 'append' . $action,
            };
            $this->$action($spec, $type, $attrs);
        }

        return $this;
    }

    /**
     * Start capture action
     *
     * @param string $captureType
     * @param string $type
     *
     * @deprecated Use twig set tag for output capturing instead.
     */
    public function captureStart($captureType = Container::APPEND, $type = 'text/javascript', array $attrs = []): void
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.4',
            'Using "captureStart()" is deprecated. Use twig set tag for output capturing instead.'
        );

        if ($this->_captureLock) {
            throw new Exception('Cannot nest headScript captures');
        }

        $this->_captureLock = true;
        $this->_captureType = $captureType;
        $this->_captureScriptType = $type;
        $this->_captureScriptAttrs = $attrs;
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
        $type = $this->_captureScriptType;
        $attrs = $this->_captureScriptAttrs;
        $this->_captureScriptType = null;
        $this->_captureScriptAttrs = null;
        $this->_captureLock = false;

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
     *
     * @return HeadScript
     *
     * @throws Exception if too few arguments or invalid method
     */
    public function __call(string $method, array $args): mixed
    {
        if (preg_match('/^(?P<action>set|(ap|pre)pend|offsetSet)(?P<mode>File|Script)$/', $method, $matches)) {
            if (1 > count($args)) {
                throw new Exception(sprintf('Method "%s" requires at least one argument', $method));
            }

            $action = $matches['action'];
            $mode = strtolower($matches['mode']);
            $type = 'text/javascript';
            $attrs = [];
            $index = null;

            if ('offsetSet' == $action) {
                $index = array_shift($args);
                if (1 > count($args)) {
                    throw new Exception(sprintf('Method "%s" requires at least two arguments, an index and source', $method));
                }
            }

            $content = is_null($args[0]) ? null : (string) $args[0];

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
                    if (!$this->_isDuplicate($content) || $action == 'set') {
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
     *
     */
    protected function _isDuplicate(string $file): bool
    {
        foreach ($this->getContainer() as $item) {
            if (($item->source === null)
                && array_key_exists('src', $item->attributes)
                && ($file == $item->attributes['src'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is the script provided valid?
     *
     *
     */
    protected function _isValid(mixed $value): bool
    {
        if ((!$value instanceof stdClass)
            || !isset($value->type)
            || (!isset($value->source) && !isset($value->attributes))) {
            return false;
        }

        return true;
    }

    /**
     * Override append
     *
     * @param  string $value
     *
     */
    public function append($value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid argument passed to append(); please use one of the helper methods, appendScript() or appendFile()');
        }

        $this->getContainer()->append($value);
    }

    /**
     * Override prepend
     *
     * @param  string $value
     *
     */
    public function prepend($value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid argument passed to prepend(); please use one of the helper methods, prependScript() or prependFile()');
        }

        $this->getContainer()->prepend($value);
    }

    /**
     * Override set
     *
     * @param  string $value
     *
     */
    public function set($value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid argument passed to set(); please use one of the helper methods, setScript() or setFile()');
        }

        $this->getContainer()->set($value);
    }

    /**
     * Override offsetSet
     *
     * @param  string|int $offset
     *
     */
    public function offsetSet($offset, mixed $value): void
    {
        if (!$this->_isValid($value)) {
            throw new Exception('Invalid argument passed to offsetSet(); please use one of the helper methods, offsetSetScript() or offsetSetFile()');
        }

        $this->getContainer()->offsetSet($offset, $value);
    }

    /**
     * Set flag indicating if arbitrary attributes are allowed
     *
     *
     * @return $this
     */
    public function setAllowArbitraryAttributes(bool $flag): static
    {
        $this->_arbitraryAttributes = $flag;

        return $this;
    }

    /**
     * Are arbitrary attributes allowed?
     *
     */
    public function arbitraryAttributesAllowed(): bool
    {
        return $this->_arbitraryAttributes;
    }

    /**
     * Create script HTML
     *
     *
     */
    public function itemToString(stdClass $item, string $indent, string $escapeStart, string $escapeEnd): string
    {
        $attrString = '';

        $type = ($this->_autoEscape) ? $this->_escape($item->type) : $item->type;
        if ($type != 'text/javascript') {
            $item->attributes['type'] = $type;
        }

        if (!empty($item->attributes)) {
            foreach ($item->attributes as $key => $value) {
                if ((!$this->arbitraryAttributesAllowed() && !in_array($key, $this->_optionalAttributes))
                    || in_array($key, ['conditional', 'noescape'])) {
                    continue;
                }
                if ('defer' == $key) {
                    $value = 'defer';
                }

                if ('async' == $key) {
                    $value = 'async';
                }
                $attrString .= sprintf(' %s="%s"', $key, ($this->_autoEscape) ? $this->_escape($value) : $value);
            }
        }

        $container = Pimcore::getContainer();

        //@phpstan-ignore-next-line
        if ($container->has('pimcore_admin_bundle.content_security_policy_handler')) {
            $cspHandler = $container->get('pimcore_admin_bundle.content_security_policy_handler');
            $attrString .= $cspHandler->getNonceHtmlAttribute();
        }

        $addScriptEscape = !(isset($item->attributes['noescape']) && filter_var($item->attributes['noescape'], FILTER_VALIDATE_BOOLEAN));

        $html = '<script' . $attrString . '>';
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
            && is_string($item->attributes['conditional'])) {
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
     *
     */
    public function toString(int|string $indent = null): string
    {
        $this->prepareEntries();

        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $useCdata = $this->useCdata ? true : false;
        $escapeStart = ($useCdata) ? '//<![CDATA[' : '//<!--';
        $escapeEnd = ($useCdata) ? '//]]>' : '//-->';

        $items = [];
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

    protected function prepareEntries(): void
    {
        foreach ($this as &$item) {
            if (!$this->_isValid($item)) {
                continue;
            }

            if ($this->isCacheBuster()) {
                // adds the automatic cache buster functionality
                if (is_array($item->attributes)) {
                    if (isset($item->attributes['src'])) {
                        $realFile = PIMCORE_WEB_ROOT . $item->attributes['src'];
                        if (file_exists($realFile)) {
                            $item->attributes['src'] = '/cache-buster-' . filemtime($realFile) . $item->attributes['src'];
                        }
                    }
                }
            }

            $event = new GenericEvent($this, [
                'item' => $item,
            ]);
            Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::VIEW_HELPER_HEAD_SCRIPT);

            if (isset($item->attributes) && is_array($item->attributes)) {
                $source = (string)($item->attributes['src'] ?? '');
                $itemAttributes = $item->attributes;

                if (isset($item->attributes['webLink'])) {
                    unset($item->attributes['webLink']);
                }

                if (!empty($source)) {
                    $this->handleWebLink($item, $source, $itemAttributes);
                }
            }
        }
    }

    /**
     * Create data item containing all necessary components of script
     *
     *
     */
    public function createData(string $type, array $attributes, string $content = null): stdClass
    {
        $data = new stdClass();
        $data->type = $type;
        $data->attributes = $attributes;
        $data->source = $content;

        return $data;
    }
}
