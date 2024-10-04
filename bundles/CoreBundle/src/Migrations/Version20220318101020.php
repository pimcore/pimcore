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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Pimcore\Model\DataObject\Objectbrick\Definition\Listing;

final class Version20220318101020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates object brick definition files';
    }

    public function up(Schema $schema): void
    {
        $this->regenerateObjectBricks();
    }

    public function down(Schema $schema): void
    {
        $this->regenerateObjectBricks();
    }

    /**
     * @throws Exception
     */
    private function regenerateObjectBricks(): void
    {
        $list = new Listing();
        foreach ($list->load() as $brickDefinition) {
            $this->write(sprintf('Saving object brick: %s', $brickDefinition->getKey()));
            $brickDefinition->save();
        }
    }
}
