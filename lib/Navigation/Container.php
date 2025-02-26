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
 * based on @author ZF1 Zend_Navigation_Container
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
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Navigation;

use Countable;
use Exception;
use RecursiveIterator;
use RecursiveIteratorIterator;

/**
 * NOTICE: Native types for `Page` are explicitly not used in this class to avoid OPCache issues
 * See this issue for details: https://github.com/pimcore/pimcore/issues/15970
 */
class Container implements RecursiveIterator, Countable
{
    /**
     * Contains sub pages
     *
     * @var Page[]
     */
    protected array $_pages = [];

    /**
     * An index that contains the order in which to iterate pages
     *
     */
    protected array $_index = [];

    /**
     * Whether index is dirty and needs to be re-arranged
     *
     */
    protected bool $_dirtyIndex = false;

    /**
     * Sorts the page index according to page order
     *
     * @internal
     */
    protected function _sort(): void
    {
        if ($this->_dirtyIndex) {
            $newIndex = [];
            $index = 0;

            foreach ($this->_pages as $hash => $page) {
                $order = $page->getOrder();
                if ($order === null) {
                    $newIndex[$hash] = $index;
                    $index++;
                } else {
                    $newIndex[$hash] = $order;
                }
            }

            asort($newIndex);
            $this->_index = $newIndex;
            $this->_dirtyIndex = false;
        }
    }

    /**
     * Notifies container that the order of pages are updated
     */
    public function notifyOrderUpdated(): void
    {
        $this->_dirtyIndex = true;
    }

    /**
     * Adds a page to the container
     *
     * This method will inject the container as the given page's parent by
     * calling {@link Page::setParent()}.
     *
     * @param array|Page $page  page to add
     *
     * @return $this fluent interface, returns self
     *
     * @throws Exception if page is invalid
     */
    public function addPage($page): static
    {
        if ($page === $this) {
            throw new Exception('A page cannot have itself as a parent');
        }

        if (is_array($page)) {
            $page = Page::factory($page);
        } elseif (!$page instanceof Page) {
            throw new Exception('Invalid argument: $page must be an instance of \Pimcore\Navigation\Page or an array');
        }

        $hash = $page->hashCode();

        if (array_key_exists($hash, $this->_index)) {
            // page is already in container
            return $this;
        }

        // adds page to container and sets dirty flag
        $this->_pages[$hash] = $page;
        $this->_index[$hash] = $page->getOrder();
        $this->_dirtyIndex = true;

        // inject self as page parent
        $page->setParent($this);

        return $this;
    }

    /**
     * Adds several pages at once
     *
     * @param Page[] $pages  pages to add
     *
     * @return $this fluent interface, returns self
     *
     * @throws Exception if $pages is not array or Container
     */
    public function addPages(array $pages): static
    {
        foreach ($pages as $page) {
            $this->addPage($page);
        }

        return $this;
    }

    /**
     * Sets pages this container should have, removing existing pages
     *
     * @param  Page[] $pages pages to set
     *
     * @return $this  fluent interface, returns self
     */
    public function setPages(array $pages): static
    {
        $this->removePages();

        return $this->addPages($pages);
    }

    /**
     * Returns pages in the container
     *
     * @return Page[]
     */
    public function getPages(): array
    {
        return $this->_pages;
    }

    /**
     * Removes the given page from the container
     *
     * @param int|Page $page page to remove, either a page instance or a specific page order
     * @param bool $recursive [optional] whether to remove recursively
     *
     * @return bool whether the removal was successful
     */
    public function removePage($page, bool $recursive = false): bool
    {
        if ($page instanceof Page) {
            $hash = $page->hashCode();
        } else {
            $this->_sort();
            if (!$hash = array_search($page, $this->_index)) {
                return false;
            }
        }

        if (isset($this->_pages[$hash])) {
            unset($this->_pages[$hash]);
            unset($this->_index[$hash]);
            $this->_dirtyIndex = true;

            return true;
        }

        if ($recursive) {
            foreach ($this->_pages as $childPage) {
                if ($childPage->hasPage($page, true)) {
                    $childPage->removePage($page, true);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Removes all pages in container
     *
     * @return $this  fluent interface, returns self
     */
    public function removePages(): static
    {
        $this->_pages = [];
        $this->_index = [];

        return $this;
    }

    /**
     * Checks if the container has the given page
     *
     * @param Page $page  page to look for
     * @param bool $recursive  [optional] whether to search recursively. Default is false.
     *
     * @return bool whether page is in container
     */
    public function hasPage($page, bool $recursive = false): bool
    {
        if (array_key_exists($page->hashCode(), $this->_index)) {
            return true;
        } elseif ($recursive) {
            foreach ($this->_pages as $childPage) {
                if ($childPage->hasPage($page, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns true if container contains any pages
     *
     * @return bool  whether container has any pages
     */
    public function hasPages(): bool
    {
        return count($this->_index) > 0;
    }

    /**
     * Returns true if container contains any visible page
     *
     * @return bool whether container has any visible page
     */
    public function hasVisiblePages(): bool
    {
        if ($this->hasPages()) {
            foreach ($this->getPages() as $page) {
                if ($page->isVisible()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns a child page matching $property == $value or
     * preg_match($value, $property), or null if not found
     *
     * @param string $property          name of property to match against
     * @param  mixed   $value             value to match property against
     * @param bool $useRegex          [optional] if true PHP's preg_match
     *                                    is used. Default is false.
     *
     * @return Page|null  matching page or null
     */
    public function findOneBy(string $property, mixed $value, bool $useRegex = false)
    {
        $iterator = new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $page) {
            $pageProperty = $page->get($property);

            // Rel and rev
            if (is_array($pageProperty)) {
                foreach ($pageProperty as $item) {
                    if (is_array($item)) {
                        // Use regex?
                        if (true === $useRegex) {
                            foreach ($item as $item2) {
                                if (preg_match($value, $item2)) {
                                    return $page;
                                }
                            }
                        } else {
                            if (in_array($value, $item)) {
                                return $page;
                            }
                        }
                    } else {
                        // Use regex?
                        if (true === $useRegex) {
                            if (preg_match($value, $item)) {
                                return $page;
                            }
                        } else {
                            if ($item == $value) {
                                return $page;
                            }
                        }
                    }
                }

                continue;
            }

            // Use regex?
            if (true === $useRegex) {
                if (preg_match($value, $pageProperty)) {
                    return $page;
                }
            } else {
                if ($pageProperty == $value) {
                    return $page;
                }
            }
        }

        return null;
    }

    /**
     * Returns all child pages matching $property == $value or
     * preg_match($value, $property), or an empty array if no pages are found
     *
     * @param string $property  name of property to match against
     * @param  mixed  $value     value to match property against
     * @param bool $useRegex  [optional] if true PHP's preg_match is used.
     *                           Default is false.
     *
     * @return Page[] array containing only Page instances
     */
    public function findAllBy(string $property, mixed $value, bool $useRegex = false): array
    {
        $found = [];

        $iterator = new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $page) {
            $pageProperty = $page->get($property);

            // Rel and rev
            if (is_array($pageProperty)) {
                foreach ($pageProperty as $item) {
                    if (is_array($item)) {
                        // Use regex?
                        if (true === $useRegex) {
                            foreach ($item as $item2) {
                                if (preg_match($value, $item2)) {
                                    $found[] = $page;

                                    break 2;
                                }
                            }
                        } else {
                            if (in_array($value, $item)) {
                                $found[] = $page;

                                break;
                            }
                        }
                    } else {
                        // Use regex?
                        if (true === $useRegex) {
                            if (preg_match($value, $item)) {
                                $found[] = $page;

                                break;
                            }
                        } else {
                            if ($item == $value) {
                                $found[] = $page;

                                break;
                            }
                        }
                    }
                }

                continue;
            }

            // Use regex?
            if (true === $useRegex) {
                if (preg_match($value, $pageProperty)) {
                    $found[] = $page;
                }
            } else {
                if ($pageProperty == $value) {
                    $found[] = $page;
                }
            }
        }

        return $found;
    }

    /**
     * Returns page(s) matching $property == $value or
     * preg_match($value, $property)
     *
     * @param string $property  name of property to match against
     * @param  mixed  $value     value to match property against
     * @param bool $all       [optional] whether an array of all matching
     *                           pages should be returned, or only the first.
     *                           If true, an array will be returned, even if not
     *                           matching pages are found. If false, null will
     *                           be returned if no matching page is found.
     *                           Default is false.
     * @param bool $useRegex  [optional] if true PHP's preg_match is used.
     *                           Default is false.
     *
     * @return Page|array<Page>|null  matching page or null
     */
    public function findBy(string $property, mixed $value, bool $all = false, bool $useRegex = false)
    {
        if ($all) {
            return $this->findAllBy($property, $value, $useRegex);
        }

        return $this->findOneBy($property, $value, $useRegex);
    }

    /**
     * Magic overload: Proxy calls to finder methods
     *
     * Examples of finder calls:
     * <code>
     * // METHOD                         // SAME AS
     * $nav->findByLabel('foo');         // $nav->findOneBy('label', 'foo');
     * $nav->findByLabel('/foo/', true); // $nav->findBy('label', '/foo/', true);
     * $nav->findOneByLabel('foo');      // $nav->findOneBy('label', 'foo');
     * $nav->findAllByClass('foo');      // $nav->findAllBy('class', 'foo');
     * </code>
     *
     * @param string $method                       method name
     * @param array $arguments                    method arguments
     *
     * @return mixed  Pimcore\Navigation|array|null    matching page, array of pages
     *                                              or null
     *
     * @throws Exception            if method does not exist
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (@preg_match('/(find(?:One|All)?By)(.+)/', $method, $match)) {
            return $this->{$match[1]}($match[2], $arguments[0], !empty($arguments[1]));
        }

        throw new Exception(sprintf('Bad method call: Unknown method %s::%s', get_class($this), $method));
    }

    /**
     * Returns an array representation of all pages in container
     *
     */
    public function toArray(): array
    {
        $pages = [];

        $this->_dirtyIndex = true;
        $this->_sort();
        $indexes = array_keys($this->_index);
        foreach ($indexes as $hash) {
            $pages[] = $this->_pages[$hash]->toArray();
        }

        return $pages;
    }

    /**
     * @return Page
     *
     * @throws Exception
     */
    public function current(): mixed
    {
        $this->_sort();
        $hash = key($this->_index);

        if (isset($this->_pages[$hash])) {
            return $this->_pages[$hash];
        }

        throw new Exception('Corruption detected in container; invalid key found in internal iterator');
    }

    public function key(): int|string|null
    {
        $this->_sort();

        return key($this->_index);
    }

    public function next(): void
    {
        $this->_sort();
        next($this->_index);
    }

    public function rewind(): void
    {
        $this->_sort();
        reset($this->_index);
    }

    public function valid(): bool
    {
        $this->_sort();

        return current($this->_index) !== false;
    }

    public function hasChildren(): bool
    {
        return $this->hasPages();
    }

    /**
     * @return ?Page
     */
    public function getChildren(): ?RecursiveIterator
    {
        $hash = key($this->_index);

        if (isset($this->_pages[$hash])) {
            return $this->_pages[$hash];
        }

        return null;
    }

    public function count(): int
    {
        return count($this->_index);
    }
}
