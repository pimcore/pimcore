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
use Pimcore\Config;

final class Version20221025165133 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename settings.yml to settings.yaml';
    }

    public function up(Schema $schema): void
    {
        $this->renameSystemFileExtension('yml', 'yaml');
    }

    public function down(Schema $schema): void
    {
        $this->renameSystemFileExtension('yaml', 'yml');
    }

    private function renameSystemFileExtension(string $fromExtension, string $toExtension): void
    {
        $file = Config::locateConfigFile('system.' . $fromExtension);
        if (file_exists($file)) {
            $pathParts = pathinfo($file);
            rename($file, $pathParts['dirname'] . '/' . $pathParts['filename'] . '.' . $toExtension);
        }
    }
}
