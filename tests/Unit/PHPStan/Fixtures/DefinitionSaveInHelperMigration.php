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

namespace Pimcore\Tests\Unit\PHPStan\Fixtures;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject\ClassDefinition;

final class DefinitionSaveInHelperMigration extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->regenerateClasses();
    }

    private function regenerateClasses(): void
    {
        foreach ((new ClassDefinition\Listing())->getClasses() as $class) {
            $class->save();
        }
    }
}
