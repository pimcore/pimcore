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
 * This classes and interfaces need to be defined if Pimcore is in v5-only mode, so without the PimcoreLegacyBundle
 * They are used in parts of the code where it isn't possible to outsource them into the PimcoreLegacyBundle
 *
 * See also:
 * pimcore/models/Object/ClassDefinition/Data/Datetime.php
 * pimcore/models/Object/ClassDefinition/Data/Date.php
 * pimcore/models/Object/Listing.php
 * pimcore/models/Document/Listing.php
 * pimcore/models/Asset/Listing.php
 * pimcore/lib/Pimcore/Log/ApplicationLogger.php
 * pimcore/lib/Pimcore/Google/Cse.php
 */



interface Zend_Paginator_Adapter_Interface extends Countable
{
    public function getItems($offset, $itemCountPerPage);
}

interface Zend_Paginator_AdapterAggregate
{
    public function getPaginatorAdapter();
}

class Zend_Date extends \Pimcore\Helper\LegacyClass {

}

class Zend_Log extends \Pimcore\Helper\LegacyClass {

}

abstract class Zend_Log_Writer_Abstract extends \Pimcore\Helper\LegacyClass {

}

