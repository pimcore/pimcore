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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_View_Helper_Navigation_HelperAbstract
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

namespace Pimcore\Navigation\Renderer;

use Pimcore\Navigation\Container;
use Pimcore\Navigation\Page;
use Symfony\Component\Templating\EngineInterface;

abstract class AbstractRenderer implements RendererInterface
{
    /**
     * @var EngineInterface
     */
    protected $templatingEngine;

    /**
     * The minimum depth a page must have to be included when rendering
     *
     * @var int
     */
    protected $_minDepth;

    /**
     * The maximum depth a page can have to be included when rendering
     *
     * @var int
     */
    protected $_maxDepth;

    /**
     * Indentation string
     *
     * @var string
     */
    protected $_indent = '';

    /**
     * Prefix for IDs when they are normalized
     *
     * @var string|null
     */
    protected $_prefixForId = null;

    /**
     * Skip current prefix for IDs when they are normalized (flag)
     *
     * @var bool
     */
    protected $_skipPrefixForId = false;

    /**
     * Wheter invisible items should be rendered by this helper
     *
     * @var bool
     */
    protected $_renderInvisible = false;

    /**
     * @param EngineInterface $templatingEngine
     */
    public function __construct(EngineInterface $templatingEngine)
    {
        $this->templatingEngine = $templatingEngine;
    }

    // Accessors:

    /**
     * Sets the minimum depth a page must have to be included when rendering
     *
     * @param  int $minDepth
     *
     * @return self  fluent interface
     */
    public function setMinDepth($minDepth = null)
    {
        if (null === $minDepth || is_int($minDepth)) {
            $this->_minDepth = $minDepth;
        } else {
            $this->_minDepth = (int) $minDepth;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getMinDepth()
    {
        if (!is_int($this->_minDepth) || $this->_minDepth < 0) {
            return 0;
        }

        return $this->_minDepth;
    }

    /**
     * @param null $maxDepth
     *
     * @return $this
     */
    public function setMaxDepth($maxDepth = null)
    {
        if (null === $maxDepth || is_int($maxDepth)) {
            $this->_maxDepth = $maxDepth;
        } else {
            $this->_maxDepth = (int) $maxDepth;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxDepth()
    {
        return $this->_maxDepth;
    }

    /**
     * @param $indent
     *
     * @return $this
     */
    public function setIndent($indent)
    {
        $this->_indent = $this->_getWhitespace($indent);

        return $this;
    }

    /**
     * @return string
     */
    public function getIndent()
    {
        return $this->_indent;
    }

    /**
     * @return string
     */
    public function getEOL()
    {
        return "\n";
    }

    /**
     * @param $prefix
     *
     * @return $this
     */
    public function setPrefixForId($prefix)
    {
        if (is_string($prefix)) {
            $this->_prefixForId = trim($prefix);
        }

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPrefixForId()
    {
        if (null === $this->_prefixForId) {
            $prefix             = get_class($this);
            $this->_prefixForId = strtolower(
                    trim(substr($prefix, strrpos($prefix, '_')), '_')
                ) . '-';
        }

        return $this->_prefixForId;
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function skipPrefixForId($flag = true)
    {
        $this->_skipPrefixForId = (bool) $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getRenderInvisible()
    {
        return $this->_renderInvisible;
    }

    /**
     * @param bool $renderInvisible
     *
     * @return $this
     */
    public function setRenderInvisible(bool $renderInvisible = true)
    {
        $this->_renderInvisible = (bool) $renderInvisible;

        return $this;
    }

    // Public methods:

    /**
     * @param Container $container
     * @param null $minDepth
     * @param int $maxDepth
     *
     * @return array
     */
    public function findActive(Container $container, $minDepth = null, $maxDepth = -1)
    {
        if (!is_int($minDepth)) {
            $minDepth = $this->getMinDepth();
        }
        if ((!is_int($maxDepth) || $maxDepth < 0) && null !== $maxDepth) {
            $maxDepth = $this->getMaxDepth();
        }

        $found  = null;
        $foundDepth = -1;
        $iterator = new \RecursiveIteratorIterator($container,
                \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $page) {
            $currDepth = $iterator->getDepth();
            if ($currDepth < $minDepth || !$this->accept($page)) {
                // page is not accepted
                continue;
            }

            if ($page->isActive(false) && $currDepth > $foundDepth) {
                // found an active page at a deeper level than before
                $found = $page;
                $foundDepth = $currDepth;
            }
        }

        if (is_int($maxDepth) && $foundDepth > $maxDepth) {
            while ($foundDepth > $maxDepth) {
                if (--$foundDepth < $minDepth) {
                    $found = null;
                    break;
                }

                $found = $found->getParent();
                if (!$found instanceof Page) {
                    $found = null;
                    break;
                }
            }
        }

        if ($found) {
            return ['page' => $found, 'depth' => $foundDepth];
        } else {
            return [];
        }
    }

    /**
     * Returns an HTML string containing an 'a' element for the given page
     *
     * @param  Page $page  page to generate HTML for
     *
     * @return string  HTML string for the given page
     */
    public function htmlify(Page $page)
    {
        $label = $page->getLabel();
        $title = $page->getTitle();

        // get attribs for anchor element
        $attribs = array_merge(
            [
                'id'     => $page->getId(),
                'title'  => $title,
                'class'  => $page->getClass(),
                'href'   => $page->getHref(),
                'target' => $page->getTarget()
            ],
            $page->getCustomHtmlAttribs()
        );

        return '<a' . $this->_htmlAttribs($attribs) . '>'
             . htmlspecialchars($label, ENT_COMPAT, 'UTF-8')
             . '</a>';
    }

    // Iterator filter methods:

    /**
     * Determines whether a page should be accepted when iterating
     *
     * Rules:
     * - If a page is not visible it is not accepted, unless RenderInvisible has
     *   been set to true.
     * - If page is accepted by the rules above and $recursive is true, the page
     *   will not be accepted if it is the descendant of a non-accepted page.
     *
     * @param  Page $page
     * @param  bool $recursive
     *
     * @return bool
     */
    public function accept(Page $page, $recursive = true)
    {
        // accept by default
        $accept = true;

        if (!$page->isVisible(false) && !$this->getRenderInvisible()) {
            // don't accept invisible pages
            $accept = false;
        }

        if ($accept && $recursive) {
            $parent = $page->getParent();
            if ($parent instanceof Page) {
                $accept = $this->accept($parent, true);
            }
        }

        return $accept;
    }

    // Util methods:

    /**
     * Retrieve whitespace representation of $indent
     *
     * @param  int|string $indent
     *
     * @return string
     */
    protected function _getWhitespace($indent)
    {
        if (is_int($indent)) {
            $indent = str_repeat(' ', $indent);
        }

        return (string) $indent;
    }

    /**
     * Converts an associative array to a string of tag attributes.
     *
     * @param  array $attribs
     *
     * @return string
     */
    protected function _htmlAttribs($attribs)
    {
        // filter out null values and empty string values
        foreach ($attribs as $key => $value) {
            if ($value === null || (is_string($value) && !strlen($value))) {
                unset($attribs[$key]);
            }
        }

        $xhtml = '';
        foreach ((array) $attribs as $key => $val) {
            $key = htmlspecialchars($key, ENT_COMPAT, 'UTF-8');

            if (('on' == substr($key, 0, 2)) || ('constraints' == $key)) {
                // Don't escape event attributes; _do_ substitute double quotes with singles
                if (!is_scalar($val)) {
                    // non-scalar data should be cast to JSON first
                    $val = json_encode($val);
                }
                // Escape single quotes inside event attribute values.
                // This will create html, where the attribute value has
                // single quotes around it, and escaped single quotes or
                // non-escaped double quotes inside of it
                $val = str_replace('\'', '&#39;', $val);
            } else {
                if (is_array($val)) {
                    $val = implode(' ', $val);
                }
                $val = htmlspecialchars($val, ENT_COMPAT, 'UTF-8');
            }

            if ('id' == $key) {
                $val = $this->_normalizeId($val);
            }

            if (strpos($val, '"') !== false) {
                $xhtml .= " $key='$val'";
            } else {
                $xhtml .= " $key=\"$val\"";
            }
        }

        return $xhtml;
    }

    /**
     * Normalize an ID
     *
     * @param  string $value    ID
     *
     * @return string           Normalized ID
     */
    protected function _normalizeId($value)
    {
        if (false === $this->_skipPrefixForId) {
            $prefix = $this->getPrefixForId();

            if (strlen($prefix)) {
                return $prefix . $value;
            }
        }

        if (strstr($value, '[')) {
            if ('[]' == substr($value, -2)) {
                $value = substr($value, 0, strlen($value) - 2);
            }
            $value = trim($value, ']');
            $value = str_replace('][', '-', $value);
            $value = str_replace('[', '-', $value);
        }

        return $value;
    }
}
