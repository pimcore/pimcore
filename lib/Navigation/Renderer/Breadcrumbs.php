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

use Exception;
use Pimcore\Navigation\Container;
use Pimcore\Navigation\Page;

class Breadcrumbs extends AbstractRenderer
{
    /**
     * Breadcrumbs separator string
     *
     */
    protected string $_separator = ' &gt; ';

    /**
     * The minimum depth a page must have to be included when rendering
     *
     */
    protected ?int $_minDepth = 1;

    /**
     * Whether last page in breadcrumb should be hyperlinked
     *
     */
    protected bool $_linkLast = false;

    /**
     * Partial view script to use for rendering menu
     *
     */
    protected string|array|null $_template = null;

    // Accessors:

    /**
     * Returns breadcrumb separator
     *
     * @return string  breadcrumb separator
     */
    public function getSeparator(): string
    {
        return $this->_separator;
    }

    public function setSeparator(string $separator): static
    {
        $this->_separator = $separator;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLinkLast(bool $linkLast): static
    {
        $this->_linkLast = $linkLast;

        return $this;
    }

    /**
     * Returns whether last page in breadcrumbs should be hyperlinked
     *
     * @return bool  whether last page in breadcrumbs should be hyperlinked
     */
    public function getLinkLast(): bool
    {
        return $this->_linkLast;
    }

    public function getTemplate(): array|string|null
    {
        return $this->_template;
    }

    /**
     * @return $this
     */
    public function setTemplate(array|string|null $template): static
    {
        $this->_template = $template;

        return $this;
    }

    /**
     * Alias of getTemplate()
     *
     */
    public function getPartial(): array|string|null
    {
        return $this->getTemplate();
    }

    /**
     * Alias of setTemplate()
     *
     *
     * @return $this
     */
    public function setPartial(string $partial): static
    {
        $this->setTemplate($partial);

        return $this;
    }

    // Render methods:

    /**
     * Get all pages between the currently active page and the container's root page.
     *
     *
     */
    public function getPages(Container $container): array
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
     *
     */
    public function renderStraight(Container $container): string
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
     *
     *
     * @throws Exception
     */
    public function renderTemplate(Container $container, ?string $partial = null): string
    {
        if (null === $partial) {
            $partial = $this->getTemplate();
        }

        if (empty($partial)) {
            throw new Exception('Unable to render menu: No partial view script provided');
        }

        $pages = $this->getPages($container);

        return $this->templatingEngine->render($partial, compact('pages'));
    }

    /**
     * Alias of renderTemplate() for ZF1 backward compatibility
     *
     *
     */
    public function renderPartial(Container $container, ?string $partial = null): string
    {
        return $this->renderTemplate($container, $partial);
    }

    public function render(Container $container): string
    {
        if ($partial = $this->getTemplate()) {
            return $this->renderPartial($container, $partial);
        } else {
            return $this->renderStraight($container);
        }
    }
}
