<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Staticroute
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Staticroute\Listing;

use Pimcore\Model;

class Dao extends Model\Dao\JsonTable {

    /**
     *
     */
    public function configure()
    {
        parent::configure();
        $this->setFile("staticroutes");
    }

    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Staticroute elements
     *
     * @return array
     */
    public function load() {

        $routesData = $this->json->fetchAll($this->model->getFilter(), $this->model->getOrder());

        $routes = array();
        foreach ($routesData as $routeData) {
            $routes[] = Model\Staticroute::getById($routeData["id"]);
        }

        $this->model->setRoutes($routes);
        return $routes;
    }

    /**
     * @return int
     */
    public function getTotalCount() {

        $routesData = $this->json->fetchAll($this->model->getFilter(), $this->model->getOrder());
        $amount = count($routesData);

        return $amount;
    }
}
