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

class Pimcore_Tool_Serialize {


    /**
     * @static
     * @param mixed $data
     * @return string
     */
    public static function serialize ($data) {
        return serialize($data);
    }

    /**
     * @static
     * @param $data
     * @return mixed
     */
    public static function unserialize ($data) {
        if(!empty($data) && is_string($data)) {
            $data = @unserialize($data);
        }
        return $data;
    }

}
