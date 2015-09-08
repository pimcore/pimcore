<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Admin\Helper;

class QueryParams {

    /**
     * @param $params
     * @return array  [orderKey => null|string, order => null|string]
     */
    public static function extractSortingSettings($params) {

        if (\Pimcore\Tool\Admin::isExtJS6()) {
            $sortParam = $params["sort"];
            if ($sortParam) {
                $sortParam = json_decode($sortParam, true);
                $sortParam = $sortParam[0];
                $orderKey = $sortParam["property"];
                $order = $sortParam["direction"];
            }
        } else {

            if($params["dir"]) {
                $order = $params["dir"];
            }

            if($params["sort"]) {
                $orderKey = $params["sort"];
            }
        }

        return ['orderKey' => $orderKey, "order" => $order];
    }

    public static function getRecordIdForGridRequest($param) {
        if (!\Pimcore\Tool\Admin::isExtJS6() && is_numeric($param)) {
            return intval($param);
        } else {
            $param = json_decode($param, true);
            return $param['id'];
        }
    }


}