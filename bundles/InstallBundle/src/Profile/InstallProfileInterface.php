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

namespace Pimcore\Bundle\InstallBundle\Profile;

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\Profile\DataSource\DataSourceInterface;

interface InstallProfileInterface
{
    public function getName(): string;

    public function getDescription(): string;

    /**
     * Bundle FQCNs to register and install.
     *
     * @return list<class-string>
     */
    public function getBundles(): array;

    /**
     * Env var definitions to collect and write.
     *
     * @return list<EnvVarDefinitionInterface>
     */
    public function getEnvVarDefinitions(): array;

    /** Data source for pre-built content, null for empty install */
    public function getDataSource(): ?DataSourceInterface;

    /**
     * Project-specific post-install commands.
     * These are merged with commands auto-collected from bundle installers.
     *
     * @return list<PostInstallCommand>
     */
    public function getPostInstallCommands(): array;

}
