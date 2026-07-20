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

namespace Pimcore\Extension\Bundle\Installer;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\Output;

class AbstractInstaller implements InstallerInterface
{
    protected BufferedOutput $output;

    public function __construct()
    {
        $this->output = new BufferedOutput(Output::VERBOSITY_NORMAL, true);
    }

    public function install(): void
    {
    }

    public function uninstall(): void
    {
    }

    public function isInstalled(): bool
    {
        return true;
    }

    public function canBeInstalled(): bool
    {
        return false;
    }

    public function canBeUninstalled(): bool
    {
        return false;
    }

    public function needsReloadAfterInstall(): bool
    {
        return false;
    }

    public function getOutput(): BufferedOutput | NullOutput
    {
        return $this->output;
    }
}
