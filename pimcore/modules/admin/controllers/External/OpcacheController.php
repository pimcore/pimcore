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
