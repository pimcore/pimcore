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
use RecursiveIteratorIterator;
use Symfony\Component\Templating\EngineInterface;

abstract class AbstractRenderer implements RendererInterface
{
    protected EngineInterface $templatingEngine;

    /**
     * The minimum depth a page must have to be included when rendering
     *
     */
    protected ?int $_minDepth = null;

    /**
     * The maximum depth a page can have to be included when rendering
     *
     */
    protected ?int $_maxDepth = null;

    /**
     * Indentation string
     *
     */
    protected string $_indent = '';

    /**
     * Prefix for IDs when they are normalized
     *
     */
    protected ?string $_prefixForId = null;

    /**
     * Skip current prefix for IDs when they are normalized (flag)
     *
     */
    protected bool $_skipPrefixForId = false;

    /**
     * Whether invisible items should be rendered by this helper
     *
     */
    protected bool $_renderInvisible = false;

    public function __construct(EngineInterface $templatingEngine)
    {
        $this->templatingEngine = $templatingEngine;
    }

    // Accessors:

    /**
     * Sets the minimum depth a page must have to be included when rendering
     *
     *
     * @return $this
     */
    public function setMinDepth(int $minDepth = null): static
    {
        $this->_minDepth = $minDepth;

        return $this;
    }

    public function getMinDepth(): ?int
    {
        if (!is_int($this->_minDepth) || $this->_minDepth < 0) {
            return 0;
        }

        return $this->_minDepth;
    }

    /**
     *
     * @return $this
     */
    public function setMaxDepth(int $maxDepth = null): static
    {
        $this->_maxDepth = $maxDepth;

        return $this;
    }

    public function getMaxDepth(): ?int
    {
        return $this->_maxDepth;
    }

    /**
     * @return $this
     */
    public function setIndent(string $indent): static
    {
        $this->_indent = $this->_getWhitespace($indent);

        return $this;
    }

    public function getIndent(): string
    {
        return $this->_indent;
    }

    public function getEOL(): string
    {
        return "\n";
    }

    /**
     * @return $this
     */
    public function setPrefixForId(string $prefix): static
    {
        $this->_prefixForId = trim($prefix);

        return $this;
    }

    public function getPrefixForId(): ?string
    {
        if (null === $this->_prefixForId) {
            $prefix = get_class($this);
            $this->_prefixForId = str_replace('\\', '-', strtolower(
                trim(substr($prefix, (int) strrpos($prefix, '_')), '_')
            )) . '-';
        }

        return $this->_prefixForId;
    }

    /**
     * @return $this
     */
    public function skipPrefixForId(bool $flag = true): static
    {
        $this->_skipPrefixForId = $flag;

        return $this;
    }

    public function getRenderInvisible(): bool
    {
        return $this->_renderInvisible;
    }

    /**
     * @return $this
     */
    public function setRenderInvisible(bool $renderInvisible = true): static
    {
        $this->_renderInvisible = $renderInvisible;

        return $this;
    }

    // Public methods:

    /**
     * @return array{page?: Page, depth?: int}
     */
    public function findActive(Container $container, int $minDepth = null, int $maxDepth = null): array
    {
        if (!is_int($minDepth)) {
            $minDepth = $this->getMinDepth();
        }
        if (!is_int($maxDepth) || $maxDepth < 0) {
            $maxDepth = $this->getMaxDepth();
        }

        $found = null;
        $foundDepth = -1;
        $iterator = new RecursiveIteratorIterator(
            $container,
            RecursiveIteratorIterator::CHILD_FIRST
        );

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
    public function htmlify(Page $page): string
    {
        $label = $page->getLabel();
        $title = $page->getTitle();

        // get attribs for anchor element
        $attribs = array_merge(
            [
                'id' => $page->getId(),
                'title' => $title,
                'class' => $page->getClass(),
                'href' => $page->getHref(),
                'target' => $page->getTarget(),
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
     *
     */
    public function accept(Page $page, bool $recursive = true): bool
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
     *
     */
    protected function _getWhitespace(int|string $indent): string
    {
        if (is_int($indent)) {
            $indent = str_repeat(' ', $indent);
        }

        return (string) $indent;
    }

    /**
     * Converts an associative array to a string of tag attributes.
     *
     *
     */
    protected function _htmlAttribs(array $attribs): string
    {
        // filter out null values and empty string values
        foreach ($attribs as $key => $value) {
            if ($value === null || (is_string($value) && !strlen($value))) {
                unset($attribs[$key]);
            }
        }

        $xhtml = '';
        foreach ($attribs as $key => $val) {
            $key = htmlspecialchars($key, ENT_COMPAT, 'UTF-8');

            if ('constraints' === $key || str_starts_with($key, 'on')) {
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

            if (str_contains($val, '"')) {
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
     * @param string $value    ID
     *
     * @return string           Normalized ID
     */
    protected function _normalizeId(string $value): string
    {
        if (false === $this->_skipPrefixForId) {
            $prefix = $this->getPrefixForId();

            if (strlen($prefix)) {
                return $prefix . $value;
            }
        }

        if (str_contains($value, '[')) {
            if (str_ends_with($value, '[]')) {
                $value = substr($value, 0, strlen($value) - 2);
            }
            $value = trim($value, ']');
            $value = str_replace('][', '-', $value);
            $value = str_replace('[', '-', $value);
        }

        return $value;
    }
}
