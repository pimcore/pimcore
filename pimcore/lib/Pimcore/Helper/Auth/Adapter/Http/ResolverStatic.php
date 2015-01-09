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

namespace Pimcore\Helper\Auth\Adapter\Http;

class ResolverStatic implements \Zend_Auth_Adapter_Http_Resolver_Interface
{
    /**
     * @var string
     */
     private $username;

    /**
     * @var string
     */
     private $password;

    /**
     * @param $username
     * @param $password
     */
     public function __construct ($username, $password) {
          $this->username = $username;
          $this->password = $password;
     }

    /**
     * @param string $username
     * @param string $realm
     * @return bool|false|string
     */
    public function resolve($username, $realm)
    {
          if($username == $this->username) {
                return $this->password;
          }

        return false;
    }
}

