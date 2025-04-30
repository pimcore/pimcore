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
use Exception;
use Pimcore\Model\DataObject\Objectbrick\Definition\Listing;

final class Version20221129084031 extends AbstractMigration
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
