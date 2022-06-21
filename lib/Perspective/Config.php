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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Perspective;

use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Model\User;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\UserRole;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @internal
 */
final class Config
{
    private const CONFIG_ID = 'perspectives';

    private static ?LocationAwareConfigRepository $locationAwareConfigRepository = null;

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const LEGACY_FILE = 'perspectives.php';

    /**
     * @return LocationAwareConfigRepository
     */
    private static function getRepository()
    {
        if (!self::$locationAwareConfigRepository) {
            $containerConfig = \Pimcore::getContainer()->getParameter('pimcore.config');
            $config = $containerConfig[self::CONFIG_ID]['definitions'];

            self::$locationAwareConfigRepository = new LocationAwareConfigRepository(
                $config,
                'pimcore_perspectives',
                $_SERVER['PIMCORE_CONFIG_STORAGE_DIR_PERSPECTIVES'] ?? PIMCORE_CONFIGURATION_DIRECTORY . '/perspectives',
                'PIMCORE_WRITE_TARGET_PERSPECTIVES',
                null,
                self::LEGACY_FILE,
            );
        }

        return self::$locationAwareConfigRepository;
    }

    /**
     * @return bool
     */
    public static function isWriteable(): bool
    {
        return self::getRepository()->isWriteable();
    }

    /**
     * @return \Pimcore\Config\Config
     */
    public static function get(): \Pimcore\Config\Config
    {
        $config = [];
        $repository = self::getRepository();
        $keys = $repository->fetchAllKeys();
        foreach ($keys as $key) {
            $configKey = $repository->loadConfigByKey(($key));
            if (isset($configKey[0])) {
                $configKey[0]['writeable'] = $repository->isWriteable($key, $configKey[1]);
                $config = array_merge($config, [$key => $configKey[0]]);
            }
        }

        if (!count($config)) {
            $config = self::getStandardPerspective();
            $config['default']['writeable'] = $repository->isWriteable();
        }

        return new \Pimcore\Config\Config($config);
    }

    /**
     * @param array $data
     * @param array|null $deletedRecords
     *
     * @throws \Exception
     */
    public static function save(array $data, ?array $deletedRecords)
    {
        $repository = self::getRepository();

        foreach ($data as $key => $value) {
            list($configKey, $dataSource) = $repository->loadConfigByKey($key);
            if ($repository->isWriteable($key, $dataSource) === true) {
                unset($value['writeable']);
                $repository->saveConfig($key, $value, function ($key, $data) {
                    return [
                        'pimcore' => [
                            'perspectives' => [
                                'definitions' => [
                                    $key => $data,
                                ],
                            ],
                        ],
                    ];
                });
            }
        }

        if ($deletedRecords) {
            foreach ($deletedRecords as $key) {
                list($configKey, $dataSource) = $repository->loadConfigByKey(($key));
                if (!empty($configKey)) {
                    $repository->deleteData($key, $dataSource);
                }
            }
        }
    }

    /**
     * @return array[]
     */
    public static function getStandardPerspective()
    {
        $elementTree = [
            [
                'type' => 'documents',
                'position' => 'left',
                'expanded' => false,
                'hidden' => false,
                'sort' => -3,
                'treeContextMenu' => [
                    'document' => [
                        'items' => [
                            'addPrintPage' => \Pimcore\Config::getWeb2PrintConfig()->get('enableInDefaultView') ? true : false, // hide add print documents by default
                        ],
                    ],
                ],
            ],
            [
                'type' => 'assets',
                'position' => 'left',
                'expanded' => false,
                'hidden' => false,
                'sort' => -2,
            ],
            [
                'type' => 'objects',
                'position' => 'left',
                'expanded' => false,
                'hidden' => false,
                'sort' => -1,
            ],
        ];

        $cvConfigs = \Pimcore\CustomView\Config::get();
        if ($cvConfigs) {
            foreach ($cvConfigs as $cvConfig) {
                if (isset($cvConfig['id'])) {
                    $elementTree[] = [
                        'type' => 'customview',
                        'id' => $cvConfig['id'],
                        'position' => $cvConfig['position'] ?? 'left',
                        'expanded' => $cvConfig['expanded'] ?? false,
                        'hidden' => $cvConfig['hidden'] ?? false,
                        'sort' => $cvConfig['sort'] ?? 999,
                    ];
                }
            }
        }

        return [
            'default' => [
                'iconCls' => 'pimcore_nav_icon_perspective',
                'elementTree' => $elementTree,
                'dashboards' => [
                    'predefined' => [
                        'welcome' => [
                            'positions' => [
                                [
                                    [
                                        'id' => 1,
                                        'type' => 'pimcore.layout.portlets.modificationStatistic',
                                        'config' => null,
                                    ],
                                    [
                                        'id' => 2,
                                        'type' => 'pimcore.layout.portlets.modifiedAssets',
                                        'config' => null,
                                    ],
                                ],
                                [
                                    [
                                        'id' => 3,
                                        'type' => 'pimcore.layout.portlets.modifiedObjects',
                                        'config' => null,
                                    ],
                                    [
                                        'id' => 4,
                                        'type' => 'pimcore.layout.portlets.modifiedDocuments',
                                        'config' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function getRuntimePerspective(User $currentUser = null)
    {
        if (null === $currentUser) {
            $currentUser = Tool\Admin::getCurrentUser();
        }

        $currentConfigName = $currentUser->getActivePerspective() ? $currentUser->getActivePerspective() : $currentUser->getFirstAllowedPerspective();

        $config = self::get()->toArray();
        $result = [];

        if (isset($config[$currentConfigName])) {
            $result = $config[$currentConfigName];
        } else {
            $availablePerspectives = self::getAvailablePerspectives($currentUser);
            if ($availablePerspectives) {
                $currentPerspective = reset($availablePerspectives);
                $currentConfigName = $currentPerspective['name'];
                if ($currentConfigName && $config[$currentConfigName]) {
                    $result = $config[$currentConfigName];
                }
            }
        }

        if ($result && $currentConfigName != $currentUser->getActivePerspective()) {
            $currentUser->setActivePerspective($currentConfigName);
            $currentUser->save();
        }

        $result['elementTree'] = self::getRuntimeElementTreeConfig($currentConfigName);

        $event = new GenericEvent(null, [
            'result' => $result,
            'configName' => $currentConfigName,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, AdminEvents::PERSPECTIVE_POST_GET_RUNTIME);

        return $event->getArgument('result');
    }

    /**
     * @internal
     *
     * @param string $name
     *
     * @return array
     */
    protected static function getRuntimeElementTreeConfig($name)
    {
        $masterConfig = self::get()->toArray();

        $config = $masterConfig[$name] ?? [];

        $tmpResult = $config['elementTree'] ?? [];

        $result = [];

        $cfConfigMapping = [];

        $cvConfigs = \Pimcore\CustomView\Config::get();

        if ($cvConfigs) {
            foreach ($cvConfigs as $node) {
                $tmpData = $node;
                if (!isset($tmpData['id'])) {
                    Logger::error('custom view ID is missing ' . var_export($tmpData, true));

                    continue;
                }

                if (!empty($tmpData['hidden'])) {
                    continue;
                }

                // backwards compatibility
                $treeType = $tmpData['treetype'] ? $tmpData['treetype'] : 'object';
                $rootNode = \Pimcore\Model\Element\Service::getElementByPath($treeType, $tmpData['rootfolder']);

                if ($rootNode) {
                    $tmpData['type'] = 'customview';
                    $tmpData['rootId'] = $rootNode->getId();
                    $tmpData['allowedClasses'] = $tmpData['classes'] ?? null;
                    $tmpData['showroot'] = (bool)$tmpData['showroot'];
                    $customViewId = $tmpData['id'];
                    $cfConfigMapping[$customViewId] = $tmpData;
                }
            }
        }

        foreach ($tmpResult as $resultItem) {
            if (!empty($resultItem['hidden'])) {
                continue;
            }

            if ($resultItem['type'] == 'customview') {
                $customViewId = $resultItem['id'] ?? false;
                if (!$customViewId) {
                    Logger::error('custom view id missing ' . var_export($resultItem, true));

                    continue;
                }
                $customViewCfg = isset($cfConfigMapping[$customViewId]) ? $cfConfigMapping[$customViewId] : null;
                if (!$customViewCfg) {
                    Logger::error('no custom view config for id  ' . $customViewId);

                    continue;
                }

                foreach ($resultItem as $specificConfigKey => $specificConfigValue) {
                    $customViewCfg[$specificConfigKey] = $specificConfigValue;
                }
                $result[] = $customViewCfg;
            } else {
                $result[] = $resultItem;
            }
        }

        usort($result, static function ($treeA, $treeB) {
            $a = $treeA['sort'] ?? 0;
            $b = $treeB['sort'] ?? 0;

            return $a <=> $b;
        });

        return $result;
    }

    public static function getAvailablePerspectives($user)
    {
        $currentConfigName = null;
        $masterConfig = self::get()->toArray();

        if ($user instanceof User) {
            if ($user->isAdmin()) {
                $config = self::get()->toArray();
            } else {
                $config = [];
                $roleIds = $user->getRoles();
                $userIds = [$user->getId()];
                $userIds = array_merge($userIds, $roleIds);

                foreach ($userIds as $userId) {
                    if (in_array($userId, $roleIds)) {
                        $userOrRoleToCheck = Role::getById($userId);
                    } else {
                        $userOrRoleToCheck = User::getById($userId);
                    }
                    if ($userOrRoleToCheck instanceof UserRole) {
                        $perspectives = $userOrRoleToCheck->getPerspectives();
                        if ($perspectives) {
                            foreach ($perspectives as $perspectiveName) {
                                $masterDef = $masterConfig[$perspectiveName] ?? null;
                                if ($masterDef) {
                                    $config[$perspectiveName] = $masterDef;
                                }
                            }
                        }
                    }
                }
                if (!$config) {
                    $config = self::get()->toArray();
                }
            }

            if ($config) {
                $tmpConfig = [];
                $validPerspectiveNames = array_keys($config);

                // sort the stuff
                foreach ($masterConfig as $masterConfigName => $masterConfiguration) {
                    if (in_array($masterConfigName, $validPerspectiveNames)) {
                        $tmpConfig[$masterConfigName] = $masterConfiguration;
                    }
                }
                $config = $tmpConfig;
            }

            $currentConfigName = $user->getActivePerspective();
            if ($config && !in_array($currentConfigName, array_keys($config))) {
                $configNames = array_keys($config);
                $currentConfigName = reset($configNames);
            }
        } else {
            $config = self::get()->toArray();
        }

        $result = [];

        foreach ($config as $configName => $configItem) {
            $item = [
                'name' => $configName,
                'icon' => $configItem['icon'] ?? null,
                'iconCls' => $configItem['iconCls'] ?? null,
            ];
            if ($user) {
                $item['active'] = $configName == $currentConfigName;
            }

            $result[] = $item;
        }

        return $result;
    }
}
