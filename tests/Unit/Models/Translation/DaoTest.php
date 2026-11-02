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

namespace Pimcore\Tests\Unit\Models\Translation;

use Doctrine\DBAL\Connection;
use Pimcore\Model\Translation;
use Pimcore\Model\Translation\Dao;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for pimcore/internal-improvements#16 — getAvailableLanguages() used to run
 * `SELECT * FROM ... GROUP BY \`language\``, which is invalid under MySQL 8's default
 * ONLY_FULL_GROUP_BY sql_mode (every non-grouped column is neither aggregated nor functionally
 * dependent on `language`). It only ever consumed the `language` column, so it's now a plain
 * `SELECT DISTINCT language`, which is ONLY_FULL_GROUP_BY-safe and behaviorally identical.
 */
class DaoTest extends TestCase
{
    public function testGetAvailableLanguagesUsesSelectDistinctNotGroupBy(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('fetchFirstColumn')
            ->with($this->callback(function (string $sql) {
                return !preg_match('/GROUP BY/i', $sql) && str_contains($sql, 'SELECT DISTINCT `language`');
            }))
            ->willReturn(['en', 'de']);

        $dao = new Dao();
        $dao->db = $connection;
        $dao->setModel(new Translation());

        $this->assertSame(['en', 'de'], $dao->getAvailableLanguages());
    }
}
