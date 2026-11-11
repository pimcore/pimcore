<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Model\DataObject\SelectOptions;

use Pimcore\Config;
use Pimcore\Model\DataObject\SelectOptions\Config as SelectOptionsConfig;
use Pimcore\Model\DataObject\SelectOptions\Config\Listing;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Select options saved to the settings store with the default read target (symfony-config), see pimcore#16682
 */
class SettingsStoreSelectOptionsTest extends ModelTestCase
{
    private const SCOPE = 'pimcore_select_options';

    private const ID = 'PmeT3964SettingsStoreOptions';

    private ?array $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousConfiguration = Config::getSystemConfiguration();
        $this->configureReadTarget('symfony-config');
    }

    protected function tearDown(): void
    {
        // deleting through the model also clears the runtime cache of the Dao
        SelectOptionsConfig::getById(self::ID)?->delete();
        SettingsStore::delete(self::ID, self::SCOPE);
        @unlink((new SelectOptionsConfig())->setId(self::ID)->getPhpClassFile());
        Config::setSystemConfiguration($this->previousConfiguration);

        parent::tearDown();
    }

    private function configureReadTarget(string $readTarget): void
    {
        $configuration = $this->previousConfiguration;
        $configuration['config_location']['select_options'] = [
            'write_target' => ['type' => 'settings-store', 'options' => ['directory' => '/tmp/unused']],
            'read_target' => ['type' => $readTarget, 'options' => ['directory' => '/tmp/unused']],
        ];
        $configuration['objects']['select_options']['definitions'] = [
            'PmeT3964FromContainer' => ['id' => 'PmeT3964FromContainer', 'selectOptions' => []],
        ];
        Config::setSystemConfiguration($configuration);
    }

    private function saveOption(): void
    {
        $config = new SelectOptionsConfig();
        $config->setId(self::ID);
        $config->setSelectOptionsFromData([
            ['value' => 'one', 'label' => 'One', 'name' => 'One'],
        ]);
        $config->save();
    }

    public function testSavedDefinitionIsFoundAndListed(): void
    {
        $this->saveOption();

        $this->assertSame(self::ID, SelectOptionsConfig::getById(self::ID)?->getId());

        $ids = array_map(static fn (SelectOptionsConfig $config) => $config->getId(), (new Listing())->load());
        $this->assertContains(self::ID, $ids);
        $this->assertContains('PmeT3964FromContainer', $ids);
    }

    public function testContainerConfigOnlyListingOfSymfonyConfigReadTargetNeedsNoSettingsStore(): void
    {
        $this->saveOption();

        $listing = (new Listing())->setContainerConfigOnly(true);
        $ids = array_map(static fn (SelectOptionsConfig $config) => $config->getId(), $listing->load());

        $this->assertSame(['PmeT3964FromContainer'], $ids);
    }

    public function testContainerConfigOnlyListingFollowsAnExplicitSettingsStoreReadTarget(): void
    {
        $this->configureReadTarget('settings-store');
        $this->saveOption();

        $listing = (new Listing())->setContainerConfigOnly(true);
        $ids = array_map(static fn (SelectOptionsConfig $config) => $config->getId(), $listing->load());

        $this->assertContains(self::ID, $ids);
        $this->assertNotContains('PmeT3964FromContainer', $ids);
    }
}
