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
 * based on @author ZF1 Zend_View_Helper_Navigation_Breadcrumbs
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

class Breadcrumbs extends AbstractRenderer
{
    /**
     * Breadcrumbs separator string
     *
     * @var string
     */
    protected $_separator = ' &gt; ';

    /**
     * The minimum depth a page must have to be included when rendering
     *
     * @var int
     */
    protected $_minDepth = 1;

    /**
     * Whether last page in breadcrumb should be hyperlinked
     *
     * @var bool
     */
    protected $_linkLast = false;

    /**
     * Partial view script to use for rendering menu
     *
     * @var string|array
     */
    protected $_template;

    // Accessors:

    /**
     * Returns breadcrumb separator
     *
     * @return string  breadcrumb separator
     */
    public function getSeparator()
    {
        return $this->_separator;
    }

    /**
     * @param string $separator
     *
     * @return $this
     */
    public function setSeparator($separator)
    {
        if (is_string($separator)) {
            $this->_separator = $separator;
        }

        return $this;
    }

    /**
     * @param bool $linkLast
     *
     * @return $this
     */
    public function setLinkLast($linkLast)
    {
        $this->_linkLast = (bool) $linkLast;

        return $this;
    }

    /**
     * Returns whether last page in breadcrumbs should be hyperlinked
     *
     * @return bool  whether last page in breadcrumbs should be hyperlinked
     */
    public function getLinkLast()
    {
        return $this->_linkLast;
    }

    /**
     * @return array|string
     */
    public function getTemplate()
    {
        return $this->_template;
    }

    /**
     * @param array|string $template
     *
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->_template = $template;

        return $this;
    }

    /**
     * Alias of getTemplate()
     *
     * @return string|array|null
     */
    public function getPartial()
    {
        return $this->getTemplate();
    }

    /**
     * Alias of setTemplate()
     *
     * @param  string $partial
     *
     * @return $this
     */
    public function setPartial($partial)
    {
        return $this->setTemplate($partial);
    }

    // Render methods:

    /**
     * Get all pages between the currently active page and the container's root page.
     *
     * @param Container $container
     *
     * @return array
     */
    public function getPages(Container $container)
    {
        $pages = [];
        if (! $active = $this->findActive($container)) {
            return [];
        }

        /** @var \Pimcore\Navigation\Page $active */
        $active = $active['page'];
        $pages[] = $active;

        while ($parent = $active->getParent()) {
            if ($parent instanceof Page) {
                $pages[] = $parent;
            } else {
                break;
            }

            if ($parent === $container) {
                // break if at the root of the given container
                break;
            }

            $active = $parent;
        }

        return array_reverse($pages);
    }

    /**
     * Renders breadcrumbs by chaining 'a' elements with the separator
     * registered in the helper
     *
     * @param Container $container
     *
     * @return string
     */
    public function renderStraight(Container $container)
    {
        // find deepest active
        if (!$active = $this->findActive($container)) {
            return '';
        }

        /** @var Page $active */
        $active = $active['page'];

        // put the deepest active page last in breadcrumbs
        if ($this->getLinkLast()) {
            $html = $this->htmlify($active);
        } else {
            $html = $active->getLabel();
            $html = htmlspecialchars($html, ENT_COMPAT, 'UTF-8');
        }

        // walk back to root
        while ($parent = $active->getParent()) {
            if ($parent instanceof Page) {
                // prepend crumb to html
                $html = $this->htmlify($parent)
                      . $this->getSeparator()
                      . $html;
            }

            if ($parent === $container) {
                // at the root of the given container
                break;
            }

            $active = $parent;
        }

        return strlen($html) ? $this->getIndent() . $html : '';
    }

    /**
     * @param Container $container
     * @param string|null $partial
     *
     * @return string
     *
     * @throws \Exception
     */
    public function renderTemplate(Container $container, string $partial = null)
    {
        if (null === $partial) {
            $partial = $this->getTemplate();
        }

        if (empty($partial)) {
            throw new \Exception('Unable to render menu: No partial view script provided');
        }

        $pages = $this->getPages($container);

        return $this->templatingEngine->render($partial, compact('pages'));
    }

    /**
     * Alias of renderTemplate() for ZF1 backward compatibility
     *
     * @param Container $container
     * @param string|null $partial
     *
     * @return string
     */
    public function renderPartial(Container $container, string $partial = null)
    {
        return $this->renderTemplate($container, $partial);
    }

    /**
     * @param Container $container
     *
     * @return string
     */
    public function render(Container $container)
    {
        if ($partial = $this->getTemplate()) {
            return $this->renderPartial($container, $partial);
        } else {
            return $this->renderStraight($container);
        }
    }
}
