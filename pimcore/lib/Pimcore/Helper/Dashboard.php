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

namespace Pimcore\Helper;

use Pimcore\Config;
use Pimcore\File;
use Pimcore\Model\User;
use Pimcore\Tool\Serialize;

class Dashboard
{
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
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    protected function getConfigDir()
    {
        return PIMCORE_CONFIGURATION_DIRECTORY.'/portal';
    }

    /**
     * @return string
     */
    protected function getConfigFile()
    {
        return $this->getConfigDir().'/dashboards_'.$this->getUser()->getId().'.psf';
    }

    /**
     * @return array|mixed
     */
    protected function loadFile()
    {
        if (!is_dir($this->getConfigDir())) {
            File::mkdir($this->getConfigDir());
        }

        if (empty($this->dashboards)) {
            if (is_file($this->getConfigFile())) {
                $dashboards = Serialize::unserialize(file_get_contents($this->getConfigFile()));
                if (!empty($dashboards)) {
                    $this->dashboards = $dashboards;
                }
            }

            if (empty($this->dashboards)) {
                $perspectiveCfg = Config::getRuntimePerspective();
                $dasboardCfg = $perspectiveCfg['dashboards'] ? $perspectiveCfg['dashboards'] : [];
                $this->dashboards = $dasboardCfg['predefined'] ? $dasboardCfg['predefined'] : [];
            }
        }

        return $this->dashboards;
    }

    /**
     * @return array|mixed
     */
    public function getAllDashboards()
    {
        return $this->loadFile();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getDashboard($key = 'welcome')
    {
        $dashboards = $this->loadFile();
        $dashboard = $dashboards[$key];

        if ($dashboard) {
            $disabledPortlets = array_keys($this->getDisabledPortlets());
            $positions = $dashboard['positions'];
            if (is_array($positions)) {
                foreach ($positions as $columnKey => $column) {
                    if ($column) {
                        foreach ($column as $portletKey => $portletCfg) {
                            $type = $portletCfg['type'];
                            if (in_array($type, $disabledPortlets)) {
                                unset($dashboard['positions'][$columnKey][$portletKey]);
                            }
                        }
                    }
                }
            }
        }

        return $dashboard ? $dashboard : ['positions' => [[], []]];
    }

    /**
     * @param $key
     * @param null $configuration
     */
    public function saveDashboard($key, $configuration = null)
    {
        $this->loadFile();

        if (empty($configuration)) {
            $configuration = ['positions' => [[], []]];
        }

        $this->dashboards[$key] = $configuration;
        File::put($this->getConfigFile(), Serialize::serialize($this->dashboards));
    }

    /**
     * @param $key
     */
    public function deleteDashboard($key)
    {
        $this->loadFile();
        unset($this->dashboards[$key]);
        File::put($this->getConfigFile(), Serialize::serialize($this->dashboards));
    }

    /**
     * @return array
     */
    public function getDisabledPortlets()
    {
        $perspectiveCfg = Config::getRuntimePerspective($this->user);
        $dasboardCfg = $perspectiveCfg['dashboards'] ? $perspectiveCfg['dashboards'] : [];

        return isset($dasboardCfg['disabledPortlets']) ? $dasboardCfg['disabledPortlets'] : [];
    }
}
