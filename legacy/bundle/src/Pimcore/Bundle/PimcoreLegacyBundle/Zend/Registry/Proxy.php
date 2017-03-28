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

namespace Pimcore\Bundle\PimcoreLegacyBundle\Zend\Registry;

class Proxy extends \Zend_Registry implements \ArrayAccess {

    public function offsetExists($offset)
    {
        return \Pimcore\Cache\Runtime::isRegistered($offset);
    }

    public function offsetGet($offset)
    {
        return \Pimcore\Cache\Runtime::get($offset);
    }

    public function offsetSet($offset, $value)
    {
        \Pimcore\Cache\Runtime::set($offset, $value);
    }

    public function offsetUnset($offset)
    {

    }
}

