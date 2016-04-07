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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


class Document_Tag_Area_ProductGrid extends Document_Tag_Area_Abstract {

    public function action() {
        /**
         * @var $filterDefinition Object_FilterDefinition
         */
        $filterDefinition = $this->view->href("productFilter")->getElement();
        $this->view->filterDefinitionObject = $filterDefinition;
    }
}