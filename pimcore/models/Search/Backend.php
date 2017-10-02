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

namespace Pimcore\Model\Search;

use Pimcore\Db;
use Pimcore\Logger;

class Backend
{
    /**
     * @var string
     */
    private $backendQuery;

    /**
     * @var array
     */
    private $backendQueryParams;

    /**
     * @param $queryStr
     * @param null $type
     * @param null $subtype
     * @param null $classname
     * @param null $modifiedRange
     * @param null $createdRange
     * @param null $userOwner
     * @param null $userModification
     * @param bool $countOnly
     */
    protected function createBackendSearchQuery($queryStr, $type= null, $subtype = null, $classname = null, $modifiedRange = null, $createdRange = null, $userOwner = null, $userModification = null, $countOnly=false)
    {
        if ($countOnly) {
            $selectFields = ' count(*) as count ';
        } else {
            $selectFields = ' * ';
        }

        $this->backendQuery = 'SELECT '.$selectFields.'
                FROM search_backend_data d
                WHERE (d.data like ? OR properties like ? )';

        $this->backendQueryParams = ["%$queryStr%", "%$queryStr%"];

        if (!empty($type)) {
            $this->backendQuery .= ' AND maintype = ? ';
            $this->backendQueryParams[] = $type;
        }

        if (!empty($subtype)) {
            $this->backendQuery .= ' AND type = ? ';
            $this->backendQueryParams[] = $subtype;
        }

        if (!empty($classname)) {
            $this->backendQuery .= ' AND subtype = ? ';
            $this->backendQueryParams[] = $classname;
        }

        if (is_array($modifiedRange)) {
            if ($modifiedRange[0] != null) {
                $this->backendQuery .= ' AND modificationDate >= ? ';
                $this->backendQueryParams[] = $modifiedRange[0];
            }
            if ($modifiedRange[1] != null) {
                $this->backendQuery .= ' AND modificationDate <= ? ';
                $this->backendQueryParams[] = $modifiedRange[1];
            }
        }

        if (is_array($createdRange)) {
            if ($createdRange[0] != null) {
                $this->backendQuery .= ' AND creationDate >= ? ';
                $this->backendQueryParams[] = $createdRange[0];
            }
            if ($createdRange[1] != null) {
                $this->backendQuery .= ' AND creationDate <= ? ';
                $this->backendQueryParams[] = $createdRange[1];
            }
        }

        if (!empty($userOwner)) {
            $this->backendQuery .= ' AND userOwner = ? ';
            $this->backendQueryParams[] = $userOwner;
        }

        if (!empty($userModification)) {
            $this->backendQuery .= ' AND userModification = ? ';
            $this->backendQueryParams[] = $userModification;
        }

        Logger::debug($this->backendQuery);
        Logger::debug($this->backendQueryParams);
    }

    /**
     * @param $queryStr
     * @param $webResourceType
     * @param $type
     * @param $subtype
     * @param $modifiedRange
     * @param $createdRange
     * @param $userOwner
     * @param $userModification
     * @param $classname
     *
     * @return int
     */
    public function getTotalSearchMatches($queryStr, $webResourceType, $type, $subtype, $modifiedRange = null, $createdRange = null, $userOwner = null, $userModification = null, $classname = null)
    {
        $this->createBackendSearchQuery($queryStr, $webResourceType, $type, $subtype, $modifiedRange, $createdRange, $userOwner, $userModification, $classname, true);
        $db = Db::get();
        $result =  $db->fetchRow($this->backendQuery, $this->backendQueryParams);
        if ($result['count']) {
            return $result['count'];
        } else {
            return 0;
        }
    }

    /**
     * @param $queryStr
     * @param null $type
     * @param null $subtype
     * @param null $classname
     * @param null $modifiedRange
     * @param null $createdRange
     * @param null $userOwner
     * @param null $userModification
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function findInDb($queryStr, $type=null, $subtype=null, $classname = null, $modifiedRange = null, $createdRange = null, $userOwner = null, $userModification = null, $offset=0, $limit=25)
    {
        $this->createBackendSearchQuery($queryStr, $type, $subtype, $classname, $modifiedRange, $createdRange, $userOwner, $userModification, false);
        $db = Db::get();

        return $db->fetchAll($this->backendQuery, $this->backendQueryParams);
    }
}
