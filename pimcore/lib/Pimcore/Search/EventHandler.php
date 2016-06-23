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

namespace Pimcore\Search;

use Pimcore\Model\Search\Backend\Data;

class EventHandler
{

    /**
     * @param $e
     */
    public static function postAddElement($e)
    {
        $searchEntry = new Data($e->getTarget());
        $searchEntry->save();
    }

    /**
     * @param $e
     */
    public static function preDeleteElement($e)
    {
        $searchEntry = Data::getForElement($e->getTarget());
        if ($searchEntry instanceof Data and $searchEntry->getId() instanceof Data\Id) {
            $searchEntry->delete();
        }
    }

    /**
     * @param $e
     */
    public static function postUpdateElement($e)
    {
        $element = $e->getTarget();
        $searchEntry = Data::getForElement($element);
        if ($searchEntry instanceof Data and $searchEntry->getId() instanceof Data\Id) {
            $searchEntry->setDataFromElement($element);
            $searchEntry->save();
        } else {
            self::postAddElement($e);
        }
    }
}
