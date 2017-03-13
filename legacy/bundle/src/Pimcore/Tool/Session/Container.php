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

namespace Pimcore\Tool\Session;

class Container extends \Zend_Session_Namespace
{

    // we need this class because Zend_Session_Namespace relies directly on the $_SESSION variable
    // which is a problem when we are using multiple sessions at once and need permanent access to the data
    // within a session namespace, so we write the data into a local variable on lock() ;-)

    /**
     * @var null
     */
    protected $lockStorage = null;

    /**
     *
     */
    public function lock()
    {
        $this->lockStorage = parent::_namespaceGet($this->_namespace);
        parent::lock();
    }

    /**
     *
     */
    public function unlock()
    {
        $this->lockStorage = null;
        parent::unlock();
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Zend_Session_Exception
     */
    public function & __get($name)
    {
        if ($this->lockStorage) {
            if (isset($this->lockStorage[$name])) {
                return $this->lockStorage[$name];
            }
        }

        return parent::_namespaceGet($this->_namespace, $name);
    }
}
