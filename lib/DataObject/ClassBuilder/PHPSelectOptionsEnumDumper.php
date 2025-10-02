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

namespace Pimcore\DataObject\ClassBuilder;

use Pimcore\Model\DataObject\SelectOptions\Config;
use Symfony\Component\Filesystem\Filesystem;

class PHPSelectOptionsEnumDumper implements PHPSelectOptionsEnumDumperInterface
{
    public function __construct(
        protected SelectOptionsEnumBuilderInterface $enumBuilder,
        protected Filesystem $filesystem,
    ) {
    }

    public function dumpPHPEnum(Config $config): void
    {
        $filePath = $config->getPhpClassFile();
        $enum = $this->enumBuilder->buildEnum($config);

        $this->filesystem->dumpFile($filePath, $enum);
    }
}
