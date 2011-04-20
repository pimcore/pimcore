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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

interface Pimcore_Model_Resource_Interface {

    /**
     * @abstract
     * @param Pimcore_Model_Abstract $model
     * @return void
     */
    public function setModel($model);

    /**
     * @abstract
     * @param  $conf
     * @return void
     */
    public function configure($conf);
}
