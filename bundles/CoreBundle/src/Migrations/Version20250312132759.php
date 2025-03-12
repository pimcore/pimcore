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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250312132759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate various asset columns from php serialized to json';
    }

    /**
     * @throws \JsonException
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $this->migrateAssets();
    }

    /**
     * @throws \JsonException
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        $this->migrateAssets(false);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    private function migrateAssets(bool $up = true): void
    {
        $assets = $this->connection->fetchAllAssociative('select * from assets');
        foreach($assets as $asset) {
            $this->migrateAsset($asset, $up);
        }
    }

    /**
     * @throws \JsonException
     */
    private function migrateAsset(array $asset, bool $up = true): void
    {
        foreach($asset as $column => $value) {
            if (
                !is_string($value) ||
                (
                    ($up === true && !preg_match('/^a:\d+:\{.*\}$/', $value)) ||
                    ($up === false && !preg_match('/^\{.*\}$/', $value))
                )
            ) {
                continue;
            }

            $data = $up ?
                unserialize($value, ['allowed_classes' => false]) :
                json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            $data = $up ?
                json_encode($data, JSON_THROW_ON_ERROR) :
                serialize($data);

            $this->addSql(
                'update assets set ' . $column . ' = ? where id = ?',
                [
                    $data,
                    $asset['id']
                ]
            );
        }
    }
}
