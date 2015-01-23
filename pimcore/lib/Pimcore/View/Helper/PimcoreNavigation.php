<?php

/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\View\Helper;

use Pimcore\Model\Document;

class PimcoreNavigation extends \Zend_View_Helper_Navigation
{
    /**
     * @var PimcoreNavigationController
     */
    public static $_controller;

    /**
     * @return PimcoreNavigationController
     */
    public static function getController()
    {
        if (!self::$_controller) {
            self::$_controller = new PimcoreNavigationController();
        }

        return self::$_controller;
    }

    /**
     * @return PimcoreNavigationController
     */
    public function pimcoreNavigation($activeDocument = null, $navigationRootDocument = null, $htmlMenuIdPrefix = null)
    {

        $controller = self::getController();

        if($activeDocument) {
            // this is the new more convenient way of creating a navigation
            $navContainer = $controller->getNavigation($activeDocument, $navigationRootDocument, $htmlMenuIdPrefix);
            $this->navigation($navContainer);
            $this->setUseTranslator(false);
            $this->setInjectTranslator(false);

            // now we need to refresh the container in all helpers, since the container can change from call to call
            // see also https://www.pimcore.org/issues/browse/PIMCORE-2636 which describes this problem in detail
            foreach($this->_helpers as $helper) {
                $helper->setContainer($this->getContainer());
            }

            // just to be sure, ... load the menu helper and set the container
            $menu = $this->findHelper("menu");
            if($menu) {
                $menu->setContainer($this->getContainer());
            }

            return $this;
        } else {
            // this is the old-style navigation
            return $controller;
        }
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, array $arguments = array())
    {
        $return = parent::__call($method, $arguments);

        // disable the translator per default, because this isn't necessary for pimcore
        if(is_object($return) && method_exists($return, "setUseTranslator")) {
            $return->setUseTranslator(false);
        }

        return $return;
    }
}

class PimcoreNavigationController
{
    /**
     * @var Document
     */
    protected $_activeDocument;

    /**
     * @var
     */
    protected $_navigationContainer;

    /**
     * @var string
     */
    protected $_htmlMenuIdPrefix;

    /**
     * @var string
     */
    protected $_pageClass = '\\Pimcore\\Navigation\\Page\\Uri';

    public function getNavigation($activeDocument, $navigationRootDocument = null, $htmlMenuIdPrefix = null)
    {

        $this->_activeDocument = $activeDocument;
        $this->_htmlMenuIdPrefix = $htmlMenuIdPrefix;

        $this->_navigationContainer = new \Zend_Navigation();

        if (!$navigationRootDocument) {
            $navigationRootDocument = Document::getById(1);
        }

        if ($navigationRootDocument->hasChilds()) {
            $this->buildNextLevel($navigationRootDocument, null, true);
        }
        return $this->_navigationContainer;
    }

    /**
     * @param $pageClass
     * @return $this
     */
    public function setPageClass($pageClass)
    {
        $this->_pageClass = $pageClass;
        return $this;
    }

    /**
     * Returns the name of the pageclass
     * 
     * @return String
     */
    public function getPageClass()
    {
        return $this->_pageClass;
    }


    /**
     * @param Document $parentDocument
     * @return Document[]
     */
    protected function getChilds($parentDocument) {
        return $parentDocument->getChilds();
    }

    /**
     * @param $parentDocument
     * @param null $parentPage
     * @param bool $isRoot
     * @return array
     */
    protected function buildNextLevel($parentDocument, $parentPage = null, $isRoot = false)
    {
        $pages = array();

        $childs = $this->getChilds($parentDocument);
        if (is_array($childs)) {
            foreach ($childs as $child) {
                $classes = "";

                if($child instanceof Document\Hardlink) {
                    $child = Document\Hardlink\Service::wrap($child);
                }

                if (($child instanceof Document\Page or $child instanceof Document\Link) and $child->getProperty("navigation_name")) {

                    $active = false;

                    if ($this->_activeDocument->getRealFullPath() == $child->getRealFullPath()) {
                        $active = true;
                    } else if (strpos($this->_activeDocument->getRealFullPath(), $child->getRealFullPath() . "/") === 0) {
                      $classes .= " active active-trail";
                    }

                    // if the child is a link, check if the target is the same as the active document
                    // if so, mark it as active
                    if($child instanceof Document\Link) {
                        if ($this->_activeDocument->getFullPath() == $child->getHref()) {
                            $active = true;
                        }

                        if (strpos($this->_activeDocument->getFullPath(), $child->getHref() . "/") === 0) {
                            $classes .= " active active-trail";
                        }
                    }

                    $path = $child->getFullPath();
                    if ($child instanceof Document\Link) {
                        $path = $child->getHref();
                    }
                    
                    $page = new $this->_pageClass();
                    $page->setUri($path . $child->getProperty("navigation_parameters") . $child->getProperty("navigation_anchor"));
                    $page->setLabel($child->getProperty("navigation_name"));
                    $page->setActive($active);
                    $page->setId($this->_htmlMenuIdPrefix . $child->getId());
                    $page->setClass($child->getProperty("navigation_class"));
                    $page->setTarget($child->getProperty("navigation_target"));
                    $page->setTitle($child->getProperty("navigation_title"));
                    $page->setAccesskey($child->getProperty("navigation_accesskey"));
                    $page->setTabindex($child->getProperty("navigation_tabindex"));
                    $page->setRelation($child->getProperty("navigation_relation"));
                    $page->setDocument($child);

                    if ($child->getProperty("navigation_exclude") || !$child->getPublished()) {
                        $page->setVisible(false);
                    }

                    if ($active and !$isRoot) {
                        $classes .= " active";
                    } else if ($active and $isRoot) {
                        $classes .= " main mainactive active";
                    } else if ($isRoot) {
                       $classes .= " main";
                    }
                    $page->setClass($page->getClass() . $classes);

                    if ($child->hasChilds()) {
                        $childPages = $this->buildNextLevel($child, $page, false);
                        $page->setPages($childPages);
                    }

                    $pages[] = $page;

                    if ($isRoot) {
                        $this->_navigationContainer->addPage($page);
                    }
                }
            }
        }
        
        return $pages;
    }

}
