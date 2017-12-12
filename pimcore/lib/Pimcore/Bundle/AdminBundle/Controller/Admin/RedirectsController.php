<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;


use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Model\Redirect;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/redirects")
 */
class RedirectsController extends AdminController
{
    /**
     * @Route("/csv-export")
     */
    public function csvExportAction(Request $request)
    {
        $this->checkPermission('redirects');

        $list = new Redirect\Listing();
        $list->setOrderKey('id');
        $list->setOrder('ASC');
        $list->load();

        $handle = fopen('php://temp', 'w+');

        $redirects = [];
        foreach ($list->getRedirects() as $redirect) {

        }


    }

    /**
     * @Route("/csv-import")
     */
    public function csvImportAction(Request $request)
    {
        $this->checkPermission('redirects');
    }
}
