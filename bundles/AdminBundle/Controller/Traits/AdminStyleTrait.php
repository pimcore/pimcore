<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Traits;

use Pimcore\Event\Admin\ElementAdminStyleEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Model\Element\AdminStyle;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
trait AdminStyleTrait
{
    /**
     * @param ElementInterface $element
     * @param null|int $context
     * @param array $data
     *
     * @throws \Exception
     */
    protected function addAdminStyle(ElementInterface $element, $context = null, &$data = [])
    {
        $event = new ElementAdminStyleEvent($element, new AdminStyle($element), $context);
        \Pimcore::getEventDispatcher()->dispatch($event, AdminEvents::RESOLVE_ELEMENT_ADMIN_STYLE);
        $adminStyle = $event->getAdminStyle();

        $data['icon'] = $adminStyle->getElementIcon() !== false ? $adminStyle->getElementIcon() : null;
        $data['iconCls'] = $adminStyle->getElementIconClass() !== false ? $adminStyle->getElementIconClass() : null;
        if ($adminStyle->getElementCssClass() !== false) {
            if (!isset($data['cls'])) {
                $data['cls'] = '';
            }
            $data['cls'] .= $adminStyle->getElementCssClass() . ' ';
        }
        $data['qtipCfg'] = $adminStyle->getElementQtipConfig();
    }
}
