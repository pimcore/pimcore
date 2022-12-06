<?php

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\User;
use Pimcore\Tests\Test\TestCase;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertNotEmpty;

class UserTest extends TestCase
{
    private const SAMPLE_USER_DATA = array(
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
        'blockedVarsForExport' => array(),
        'options' => null,
        'width' => '',
        'defaultValue' => null,
        'optionsProviderClass' => null,
        'optionsProviderData' => null,
        'columnLength' => 190,
        'dynamicOptions' => false,
        'defaultValueGenerator' => '',
        'unique' => false,
    );

    private bool $inAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inAdmin = \Pimcore::inAdmin();
    }

    protected function tearDown(): void
    {
        if ($this->inAdmin) {
            \Pimcore::setAdminMode();
        } else {
            \Pimcore::unsetAdminMode();
        }

        parent::tearDown();
    }

    public function test__set_stateDoesNotPopulateSelectOptionsWhenNotInAdminMode()
    {
        \Pimcore::unsetAdminMode();

        $user = User::__set_state(self::SAMPLE_USER_DATA);

        assertEmpty($user->getOptions());
    }

    public function test__set_statePopulatesSelectOptionsIbAdminMode()
    {
        \Pimcore::setAdminMode();

        $user = User::__set_state(self::SAMPLE_USER_DATA);

        assertNotEmpty($user->getOptions());
    }
}
