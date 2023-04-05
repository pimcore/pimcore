<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\Helper;

use Pimcore\Bundle\AdminBundle\Perspective\Config;
use Pimcore\Model\User;
use Pimcore\Tool\Serialize;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
final class Dashboard
{
    protected User $user;

    protected ?array $dashboards = null;

    protected Filesystem $filesystem;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->filesystem = new Filesystem();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    protected function getConfigDir(): string
    {
        return PIMCORE_CONFIGURATION_DIRECTORY.'/portal';
    }

    protected function getConfigFile(): string
    {
        return $this->getConfigDir().'/dashboards_'.$this->getUser()->getId().'.psf';
    }

    protected function loadFile(): ?array
    {
        if (!is_dir($this->getConfigDir())) {
            $this->filesystem->mkdir($this->getConfigDir(), 0775);
        }

        if (empty($this->dashboards)) {
            if (is_file($this->getConfigFile())) {
                $dashboards = Serialize::unserialize(file_get_contents($this->getConfigFile()));
                if (!empty($dashboards)) {
                    $this->dashboards = $dashboards;
                }
            }

            $perspectiveCfg = Config::getRuntimePerspective();
            $dashboardCfg = $perspectiveCfg['dashboards'] ?? [];
            $dashboardsPerspective = $dashboardCfg['predefined'] ?? [];

            if (empty($this->dashboards)) {
                $this->dashboards = $dashboardsPerspective;
            } else {
                foreach ($dashboardsPerspective as $key => $dashboard) {
                    if (!isset($this->dashboards[$key])) {
                        $this->dashboards[$key] = $dashboard;
                    }
                }
            }
        }

        return $this->dashboards;
    }

    public function getAllDashboards(): ?array
    {
        return $this->loadFile();
    }

    public function getDashboard(string $key = 'welcome'): array
    {
        $dashboards = $this->loadFile();
        $dashboard = $dashboards[$key] ?? null;

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
     * @param string $key
     * @param array|null $configuration
     */
    public function saveDashboard(string $key, array $configuration = null): void
    {
        $this->loadFile();

        if (empty($configuration)) {
            $configuration = ['positions' => [[], []]];
        }

        $this->dashboards[$key] = $configuration;
        $this->filesystem->dumpFile($this->getConfigFile(), Serialize::serialize($this->dashboards));
    }

    public function deleteDashboard(string $key): void
    {
        $this->loadFile();
        unset($this->dashboards[$key]);
        $this->filesystem->dumpFile($this->getConfigFile(), Serialize::serialize($this->dashboards));
    }

    public function getDisabledPortlets(): array
    {
        $perspectiveCfg = Config::getRuntimePerspective($this->user);
        $dashboardCfg = $perspectiveCfg['dashboards'] ?? [];

        return $dashboardCfg['disabledPortlets'] ?? [];
    }
}
