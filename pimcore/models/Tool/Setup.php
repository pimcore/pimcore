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
 * @category   Pimcore
 * @package    Tool
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool;

use Pimcore\File;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\Setup\Dao getDao()
 * @method void database()
 * @method void setDbConnection($db)
 * @method void insertDump($dbDataFile)
 */
class Setup extends Model\AbstractModel
{
    /**
     * @param array $config
     */
    public function config($config = [])
    {
        $settings = null;

        // check for an initial configuration template
        // used eg. by the demo installer
        $configTemplatePath = PIMCORE_CONFIGURATION_DIRECTORY . '/system.php';
        if (file_exists($configTemplatePath)) {
            try {
                $configTemplate = new \Pimcore\Config\Config(include($configTemplatePath));
                if ($configTemplate->general) { // check if the template contains a valid configuration
                    $settings = $configTemplate->toArray();

                    // unset database configuration
                    unset($settings['database']['params']['host']);
                    unset($settings['database']['params']['port']);
                }
            } catch (\Exception $e) {
            }
        }

        // set default configuration if no template is present
        if (!$settings) {
            // write configuration file
            $settings = [
                'general' => [
                    'timezone' => 'Europe/Berlin',
                    'language' => 'en',
                    'validLanguages' => 'en',
                ],
                'database' => [
                    'params' => [
                        'username' => 'root',
                        'password' => '',
                        'dbname' => '',
                    ]
                ],
                'documents' => [
                    'versions' => [
                        'steps' => '10'
                    ],
                    'default_controller' => 'default',
                    'default_action' => 'default',
                    'error_pages' => [
                        'default' => '/'
                    ],
                    'createredirectwhenmoved' => '',
                    'allowtrailingslash' => 'no',
                    'generatepreview' => '1'
                ],
                'objects' => [
                    'versions' => [
                        'steps' => '10'
                    ]
                ],
                'assets' => [
                    'versions' => [
                        'steps' => '10'
                    ]
                ],
                'services' => [],
                'cache' => [
                    'excludeCookie' => ''
                ],
                'httpclient' => [
                    'adapter' => 'Socket'
                ],
                'email' => [
                    'sender' => [
                        'name' => '',
                        'email' => ''
                    ],
                    'return' => [
                        'name' => '',
                        'email' => ''
                    ],
                    'method' => 'mail',
                    'smtp' => [
                        'host' => '',
                        'port' => '',
                        'ssl' => null,
                        'name' => '',
                        'auth' => [
                            'method' => null,
                            'username' => '',
                            'password' => ''
                        ]
                    ],
                    'debug' => [
                        'emailaddresses' => ''
                    ]
                ],
                'newsletter' => [
                    'sender' => [
                        'name' => '',
                        'email' => ''
                    ],
                    'return' => [
                        'name' => '',
                        'email' => ''
                    ],
                    'method' => 'mail',
                    'smtp' => [
                        'host' => '',
                        'port' => '',
                        'ssl' => null,
                        'name' => '',
                        'auth' => [
                            'method' => null,
                            'username' => '',
                            'password' => ''
                        ]
                    ],
                    'usespecific' => ''
                ]
            ];
        }

        $settings = array_replace_recursive($settings, $config);

        $configFile = \Pimcore\Config::locateConfigFile('system.php');
        File::putPhpFile($configFile, to_php_data_file_format($settings));

        File::putPhpFile(PIMCORE_CONFIGURATION_DIRECTORY . '/debug-mode.php', to_php_data_file_format([
            'active' => true,
            'ip' => '',
        ]));

        // generate parameters.yml
        $parameters = file_get_contents(PIMCORE_APP_ROOT . '/config/parameters.example.yml');
        $parameters = str_replace('ThisTokenIsNotSoSecretChangeIt', generateRandomSymfonySecret(), $parameters);
        File::put(PIMCORE_APP_ROOT . '/config/parameters.yml', $parameters);
    }

    /**
     * @param array $config
     */
    public function contents($config = [])
    {
        $this->getDao()->contents();
        $this->createOrUpdateUser($config);
    }

    /**
     * @param array $config
     */
    public function createOrUpdateUser($config = [])
    {
        $defaultConfig = [
            'username' => 'admin',
            'password' => md5(microtime())
        ];

        $settings = array_replace_recursive($defaultConfig, $config);

        if ($user = Model\User::getByName($settings['username'])) {
            $user->delete();
        }

        $user = Model\User::create([
            'parentId' => 0,
            'username' => $settings['username'],
            'password' => \Pimcore\Tool\Authentication::getPasswordHash($settings['username'], $settings['password']),
            'active' => true
        ]);
        $user->setAdmin(true);
        $user->save();
    }
}
