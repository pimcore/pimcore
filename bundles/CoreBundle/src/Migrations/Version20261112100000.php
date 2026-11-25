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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject\Classificationstore\IdentifierBackfill;

/**
 * Gives every classification store row that has no identifier a generated one, by the routine of
 * `pimcore:classificationstore:backfill-identifiers`. Rows which already have an identifier are not touched.
 */
final class Version20261112100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Assign a generated identifier to the classification store rows which do not have one yet';
    }

    /**
     * The backfill clears the shared caches once its writes are done; inside a transaction that would happen
     * before the commit, and a request in between would cache the pre-backfill rows again.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        [$written, $unresolved] = (new IdentifierBackfill())->run($this->connection);

        $this->write(sprintf('%d classification store row(s) backfilled.', $written));

        foreach ($unresolved as $row) {
            $this->write(IdentifierBackfill::formatUnresolved($row));
        }
    }

    public function down(Schema $schema): void
    {
        $this->write('Nothing to revert: the generated identifiers are kept.');
    }
}
