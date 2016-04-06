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

namespace Pimcore\Admin\Helper;

class QueryParams
{

    /**
     * @param $params
     * @return array  [orderKey => null|string, order => null|string]
     */
    public static function extractSortingSettings($params)
    {
        $orderKey = null;
        $order = null;

        if (\Pimcore\Tool\Admin::isExtJS6()) {
            $sortParam = $params["sort"];
            if ($sortParam) {
                $sortParam = json_decode($sortParam, true);
                $sortParam = $sortParam[0];
                $orderKey = $sortParam["property"];
                $order = $sortParam["direction"];
            }
        } else {
            if ($params["dir"]) {
                $order = $params["dir"];
            }

            if ($params["sort"]) {
                $orderKey = $params["sort"];
            }
        }

        return ['orderKey' => $orderKey, "order" => $order];
    }

    public static function getRecordIdForGridRequest($param)
    {
        if (!\Pimcore\Tool\Admin::isExtJS6() && is_numeric($param)) {
            return intval($param);
        } else {
            $param = json_decode($param, true);
            return $param['id'];
        }
    }
}
