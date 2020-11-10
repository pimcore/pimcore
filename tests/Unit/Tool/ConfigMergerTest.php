<?php

declare(strict_types=1);

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

namespace Pimcore\Tests\Unit\Tool;

use Pimcore\DependencyInjection\ConfigMerger;
use Pimcore\Tests\Test\TestCase;

/**
 * @deprecated will be removed in Pimcore 7
 */
class ConfigMergerTest extends TestCase
{
    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configMerger = new ConfigMerger();
    }

    public function testMerge()
    {
        $coreConfig = [
            'providers' => [
                'pimcore_admin' => [
                    'id' => 'Pimcore\\Bundle\\AdminBundle\\Security\\User\\UserProvider',
                ],
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                    'security' => false,
                ],
                'admin' => [
                    'anonymous' => null,
                    'pattern' => '^/admin',
                    'stateless' => true,
                    'provider' => 'pimcore_admin',
                    'logout' => [
                        'path' => '/admin/logout',
                        'target' => '/admin/login',
                        'success_handler' => 'Pimcore\\Bundle\\AdminBundle\\Security\\LogoutSuccessHandler',
                    ],
                    'guard' => [
                        'entry_point' => 'Pimcore\\Bundle\\AdminBundle\\Security\\Guard\\AdminAuthenticator',
                        'authenticators' => [
                            'Pimcore\\Bundle\\AdminBundle\\Security\\Guard\\AdminAuthenticator',
                        ],
                    ],
                ],
            ],
            'access_control' => [
                [
                    'path' => '^/admin/settings/display-custom-logo',
                    'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY',
                ],
                [
                    'path' => '^/admin/login$',
                    'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY',
                ],
                [
                    'path' => '^/admin/login/(login|lostpassword|deeplink)$',
                    'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY',
                ],
                [
                    'path' => '^/admin',
                    'roles' => 'ROLE_PIMCORE_USER',
                ],
            ],
            'role_hierarchy' => [
                'ROLE_PIMCORE_ADMIN' => ['ROLE_PIMCORE_USER'],
            ],
        ];

        $appConfig = [
            'providers' => [
                'demo_cms_provider' => [
                    'id' => 'app.security.user_provider',
                ],
            ],
            'firewalls' => [
                'demo_cms_fw' => [
                    'anonymous' => null,
                    'provider' => 'demo_cms_provider',
                    'form_login' => [
                        'login_path' => 'demo_login',
                        'check_path' => 'demo_login',
                    ],
                    'logout' => [
                        'path' => 'demo_logout',
                        'target' => 'demo_login',
                        'invalidate_session' => false,
                    ],
                    'logout_on_user_change' => true,
                ],
            ],
            'access_control' => [
                [
                    'path' => '^/secure',
                    'roles' => 'ROLE_USER',
                ],
                [
                    'path' => '^/secure/admin',
                    'roles' => 'ROLE_ADMIN',
                ],
            ],
            'role_hierarchy' => [
                'ROLE_ADMIN' => ['ROLE_USER'],
            ],
        ];

        $merged = $this->configMerger->merge($coreConfig, $appConfig);

        $this->assertEquals([
            'providers' => [
                'pimcore_admin' => [
                    'id' => 'Pimcore\\Bundle\\AdminBundle\\Security\\User\\UserProvider',
                ],
                'demo_cms_provider' => [
                    'id' => 'app.security.user_provider',
                ],
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                    'security' => false,
                ],
                'admin' => [
                    'anonymous' => null,
                    'pattern' => '^/admin',
                    'stateless' => true,
                    'provider' => 'pimcore_admin',
                    'logout' => [
                        'path' => '/admin/logout',
                        'target' => '/admin/login',
                        'success_handler' => 'Pimcore\\Bundle\\AdminBundle\\Security\\LogoutSuccessHandler',
                    ],
                    'guard' => [
                        'entry_point' => 'Pimcore\\Bundle\\AdminBundle\\Security\\Guard\\AdminAuthenticator',
                        'authenticators' => [
                            'Pimcore\\Bundle\\AdminBundle\\Security\\Guard\\AdminAuthenticator',
                        ],
                    ],
                ],
                'demo_cms_fw' => [
                    'anonymous' => null,
                    'provider' => 'demo_cms_provider',
                    'form_login' => [
                        'login_path' => 'demo_login',
                        'check_path' => 'demo_login',
                    ],
                    'logout' => [
                        'path' => 'demo_logout',
                        'target' => 'demo_login',
                        'invalidate_session' => false,
                    ],
                    'logout_on_user_change' => true,
                ],
            ],
            'access_control' => [
                [
                    'path' => '^/admin/settings/display-custom-logo',
                    'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY',
                ],
                [
                    'path' => '^/admin/login$',
                    'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY',
                ],
                [
                    'path' => '^/admin/login/(login|lostpassword|deeplink)$',
                    'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY',
                ],
                [
                    'path' => '^/admin',
                    'roles' => 'ROLE_PIMCORE_USER',
                ],
                [
                    'path' => '^/secure',
                    'roles' => 'ROLE_USER',
                ],
                [
                    'path' => '^/secure/admin',
                    'roles' => 'ROLE_ADMIN',
                ],
            ],
            'role_hierarchy' => [
                'ROLE_PIMCORE_ADMIN' => ['ROLE_PIMCORE_USER'],
                'ROLE_ADMIN' => ['ROLE_USER'],
            ],
        ], $merged);
    }

    public function testIndexedArraysAreExtended()
    {
        $arr1 = [
            'foo' => [
                'A', 'B', 'C',
            ],
        ];

        $arr2 = [
            'foo' => [
                'D', 'E', 'F',
            ],
        ];

        $this->assertEquals([
            'foo' => ['A', 'B', 'C', 'D', 'E', 'F'],
        ], $this->configMerger->merge($arr1, $arr2));
    }

    public function testAssociativeArraysAreMerged()
    {
        $arr1 = [
            'foo' => [
                'bar' => 'baz',
                'tooverwrite' => 'xyz',
                'tomerge' => [
                    'A', 'B',
                ],
            ],
        ];

        $arr2 = [
            'foo' => [
                'baz' => 'inga',
                'tooverwrite' => 'abc',
                'tomerge' => [
                    'C', 'D',
                ],
            ],
        ];

        $this->assertEquals([
            'foo' => [
                'bar' => 'baz',
                'baz' => 'inga',
                'tooverwrite' => 'abc',
                'tomerge' => [
                    'A', 'B', 'C', 'D',
                ],
            ],
        ], $this->configMerger->merge($arr1, $arr2));
    }

    public function testIndexedArraysAreNotOverwrittenWithNull()
    {
        $arr1 = [
            'access_control' => [
                [
                    'path' => '^/secure',
                    'roles' => 'ROLE_USER',
                ],
                [
                    'path' => '^/secure/admin',
                    'roles' => 'ROLE_ADMIN',
                ],
            ],
        ];

        $arr2 = [
            'access_control' => null,
        ];

        $this->assertEquals($arr1, $this->configMerger->merge($arr1, $arr2));
    }

    public function testIndexedArraysOverwriteNull()
    {
        $arr1 = [
            'access_control' => null,
        ];

        $arr2 = [
            'access_control' => [
                [
                    'path' => '^/secure',
                    'roles' => 'ROLE_USER',
                ],
                [
                    'path' => '^/secure/admin',
                    'roles' => 'ROLE_ADMIN',
                ],
            ],
        ];

        $this->assertEquals($arr2, $this->configMerger->merge($arr1, $arr2));
    }

    public function testAssociativeArraysAreNotOverwrittenWithNull()
    {
        $arr1 = [
            'firewalls' => [
                'demo_cms_fw' => [
                    'anonymous' => null,
                    'provider' => 'demo_cms_provider',
                    'form_login' => [
                        'login_path' => 'demo_login',
                        'check_path' => 'demo_login',
                    ],
                    'logout' => [
                        'path' => 'demo_logout',
                        'target' => 'demo_login',
                        'invalidate_session' => false,
                    ],
                    'logout_on_user_change' => true,
                ],
            ],
        ];

        $arr2 = [
            'firewalls' => [
                'demo_cms_fw' => null,
            ],
        ];

        $this->assertEquals($arr1, $this->configMerger->merge($arr1, $arr2));
    }

    public function testAssociativeArraysOverwriteNull()
    {
        $arr1 = [
            'firewalls' => [
                'demo_cms_fw' => null,
            ],
        ];

        $arr2 = [
            'firewalls' => [
                'demo_cms_fw' => [
                    'anonymous' => null,
                    'provider' => 'demo_cms_provider',
                    'form_login' => [
                        'login_path' => 'demo_login',
                        'check_path' => 'demo_login',
                    ],
                    'logout' => [
                        'path' => 'demo_logout',
                        'target' => 'demo_login',
                        'invalidate_session' => false,
                    ],
                    'logout_on_user_change' => true,
                ],
            ],
        ];

        $this->assertEquals($arr2, $this->configMerger->merge($arr1, $arr2));
    }
}
