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

namespace Pimcore\File;

class Transfer extends \Zend_File_Transfer{

    /**
     * @param string $adapter
     * @param bool $direction
     * @param array $options
     */
    public function __construct($adapter = "\\Pimcore\\File\\Transfer\\Adapter\\Http", $direction = false, $options = array())
    {
        parent::__construct($adapter,$direction,$options);
    }
}