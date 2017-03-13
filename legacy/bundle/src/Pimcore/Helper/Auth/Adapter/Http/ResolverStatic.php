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
     public function __construct($username, $password)
     {
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
        if ($username == $this->username) {
            return $this->password;
        }

        return false;
    }
}
