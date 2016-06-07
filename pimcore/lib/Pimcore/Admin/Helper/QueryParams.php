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
        $orderByFeature = null;

        if (\Pimcore\Tool\Admin::isExtJS6()) {
            $sortParam = isset($params["sort"]) ? $params["sort"] : false;
            if ($sortParam) {
                $sortParam = json_decode($sortParam, true);
                $sortParam = $sortParam[0];

                if (substr($sortParam["property"], 0, 1) != "~") {
                    $orderKey = $sortParam["property"];
                    $order = $sortParam["direction"];
                } else {
                    $orderKey = $sortParam["property"];
                    $order = $sortParam["direction"];

                    $parts = explode("~", $orderKey);

                    $fieldname = $parts[2];
                    $groupKeyId = $parts[3];
                    $groupKeyId = explode("-", $groupKeyId);
                    $groupId = $groupKeyId[0];
                    $keyid = $groupKeyId[1];
                    return ['fieldname' => $fieldname, 'groupId' => $groupId, "keyId"=> $keyid, "order" => $order, "isFeature" => 1];
                }
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
