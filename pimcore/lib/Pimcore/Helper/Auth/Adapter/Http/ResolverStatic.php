<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
