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

class Pimcore_Helper_Auth_Adapter_Http_Resolver_Static implements Zend_Auth_Adapter_Http_Resolver_Interface
{

     private $username;
     private $password;

     public function __construct ($username, $password) {
          $this->username = $username;
          $this->password = $password;
     }

    public function resolve($username, $realm)
    {
          if($username == $this->username) {
                return $this->password;
          }

        return false;
    }
}

