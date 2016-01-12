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

namespace Pimcore\Tool\Session;

class Container extends \Zend_Session_Namespace {

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
        if($this->lockStorage) {
            if(isset($this->lockStorage[$name])) {
                return $this->lockStorage[$name];
            }
        }

        return parent::_namespaceGet($this->_namespace, $name);
    }
}
