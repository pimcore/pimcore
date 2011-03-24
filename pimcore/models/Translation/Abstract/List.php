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
 * @category   Pimcore
 * @package    Translation
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Translation_Abstract_List extends Pimcore_Model_List_Abstract {

    /**
     * Contains the results of the list. They are all an instance of Staticroute
     *
     * @var array
     */
    public $translations = array();

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return array
     */
    public function getTranslations() {
        return $this->translations;
    }

    /**
     * @param array $translations
     * @return void
     */
    public function setTranslations($translations) {
        $this->translations = $translations;
    }
}
