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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition\Data\User;
use Pimcore\Tests\Support\Test\TestCase;

class UserTest extends TestCase
{
    private const SAMPLE_USER_DATA = [
        'name' => 'pimcoreUser',
        'title' => 'Pimcore User',
        'tooltip' => '',
        'mandatory' => false,
        'noteditable' => false,
        'index' => false,
        'locked' => false,
        'style' => '',
        'permissions' => null,
        'datatype' => 'data',
        'fieldtype' => 'user',
        'relationType' => false,
        'invisible' => false,
        'visibleGridView' => false,
        'visibleSearch' => false,
        'blockedVarsForExport' => [],
        'options' => null,
        'width' => '',
        'defaultValue' => null,
        'optionsProviderClass' => null,
        'optionsProviderData' => null,
        'columnLength' => 190,
        'dynamicOptions' => false,
        'defaultValueGenerator' => '',
        'unique' => false,
    ];

    private bool $inAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inAdmin = Pimcore::inAdmin();
    }

    protected function tearDown(): void
    {
        if ($this->inAdmin) {
            Pimcore::setAdminMode();
        } else {
            Pimcore::unsetAdminMode();
        }

        parent::tearDown();
    }

    public function test__set_stateDoesNotPopulateSelectOptionsWhenNotInAdminMode(): void
    {
        Pimcore::unsetAdminMode();

        $user = User::__set_state(self::SAMPLE_USER_DATA);

        $this->assertEmpty($user->getOptions());
    }

    public function test__set_statePopulatesSelectOptionsIbAdminMode(): void
    {
        Pimcore::setAdminMode();

        $user = User::__set_state(self::SAMPLE_USER_DATA);

        $this->assertNotEmpty($user->getOptions());
    }
}
