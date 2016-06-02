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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use \Linfo\Exceptions\FatalException;
use \Linfo\Linfo;
use \Linfo\Common;

class Admin_External_OpcacheController extends \Pimcore\Controller\Action\Admin
{
    public function init()
    {
        parent::init();

        // only for admins
        $this->checkPermission("opcache");
    }

    public function indexAction()
    {
        $path = PIMCORE_DOCUMENT_ROOT . '/vendor/amnuts/opcache-gui';

        include($path . "/index.php");

        $this->removeViewRenderer();
    }
}
