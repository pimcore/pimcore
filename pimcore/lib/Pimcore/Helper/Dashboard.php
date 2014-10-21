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

namespace Pimcore\Helper;

use Pimcore\Tool\Serialize; 
use Pimcore\File;
use Pimcore\Model\User;

class Dashboard {

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $dashboards;

    /**
     * @param User $user
     */
    public function __construct(User $user) {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @return string
     */
    protected function getConfigDir () {
        return PIMCORE_CONFIGURATION_DIRECTORY."/portal";
    }

    /**
     * @return string
     */
    protected function getConfigFile () {
        return $this->getConfigDir()."/dashboards_".$this->getUser()->getId().".psf";
    }

    /**
     * @return array|mixed
     */
    protected function loadFile() {
        if(!is_dir($this->getConfigDir())) {
            File::mkdir($this->getConfigDir());
        }

        if(empty($this->dashboards)) {

            if(is_file($this->getConfigFile())) {
                $dashboards = Serialize::unserialize(file_get_contents($this->getConfigFile()));
                if(!empty($dashboards)) {
                    $this->dashboards = $dashboards;
                }
            }

            if(empty($this->dashboards)) {

                // if no configuration exists, return the base config
                $this->dashboards = array(
                    "welcome" => array(
                        "positions" => array(
                            array(
                                array(
                                    "id" => 1,
                                    "type" => "pimcore.layout.portlets.modificationStatistic",
                                    "config" => null
                                ),
                                array(
                                    "id" => 2,
                                    "type" => "pimcore.layout.portlets.modifiedAssets",
                                    "config" => null
                                )
                            ),
                            array(
                                array(
                                    "id" => 3,
                                    "type" => "pimcore.layout.portlets.modifiedObjects",
                                    "config" => null
                                ),
                                array(
                                    "id" => 4,
                                    "type" => "pimcore.layout.portlets.modifiedDocuments",
                                    "config" => null
                                )
                            )
                        )
                    )
                );
            }
        }
        return $this->dashboards;
    }

    /**
     * @return array|mixed
     */
    public function getAllDashboards() {
        return $this->loadFile();
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getDashboard($key = "welcome") {
        $dashboards = $this->loadFile();
        return $dashboards[$key];
    }

    /**
     * @param $key
     * @param null $configuration
     */
    public function saveDashboard($key, $configuration = null) {
        $this->loadFile();

        if(empty($configuration)) {
            $configuration = array("positions" => array(array(), array()));
        }

        $this->dashboards[$key] = $configuration;
        File::put($this->getConfigFile(), Serialize::serialize($this->dashboards));
    }

    /**
     * @param $key
     */
    public function deleteDashboard($key) {
        $this->loadFile();
        unset($this->dashboards[$key]);
        File::put($this->getConfigFile(), Serialize::serialize($this->dashboards));
    }

}