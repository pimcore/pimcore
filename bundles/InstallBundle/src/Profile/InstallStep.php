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

enum InstallStep: string
{
    // Phase 1
    /** Collects and validates environment variable values. External service connections. */
    case CollectAndValidate = 'collect_validate';

    /** Writes collected env vars to .env.local. File operation. */
    case WriteEnv = 'write_env';

    /** Writes doctrine_mapping_types.yaml config. File operation. */
    case WriteDoctrineConfig = 'write_doctrine_config';

    // Phase 2
    /** Boots the real application kernel. Runtime operation. */
    case BootKernel = 'boot_kernel';

    /** Creates database schema, infrastructure tables, and seed data. Database operation. */
    case SetupDatabase = 'setup_database';

    /** Imports data from the profile's data source. Database operation. Conditional — only runs if getDataSource() is non-null. */
    case ImportData = 'import_data';

    /** Creates or replaces the admin user. Database operation. */
    case CreateAdmin = 'create_admin';

    /** Writes bundle FQCNs to config/bundles.php. File operation. */
    case RegisterBundles = 'register_bundles';

    /** Clears cache and reboots kernel with newly registered bundles. File + runtime operation. */
    case RebootKernel = 'reboot_kernel';

    /** Runs pimcore:bundle:install for each profile bundle. Database + subprocess. */
    case InstallBundles = 'install_bundles';

    /** Runs assets:install. File operation + subprocess. */
    case InstallAssets = 'install_assets';

    /** Runs pimcore:deployment:classes-rebuild. File + database + subprocess. Non-fatal. */
    case RebuildClasses = 'rebuild_classes';

    /** Marks all Pimcore migrations as executed. Database + subprocess. Non-fatal. */
    case MarkMigrations = 'mark_migrations';

    /** Executes post-install commands from profile, bundle installers, and CLI providers. Mixed + subprocess. */
    case PostInstallCommands = 'post_install_commands';

    /** Runs pimcore:maintenance. Mixed + subprocess. Non-fatal. */
    case RunMaintenance = 'run_maintenance';

    /** Runs profile's postInstall() hook. Conditional — only runs if profile implements PostInstallHookInterface. */
    case ProfilePostInstall = 'profile_post_install';

    /** Clears cache and removes needs-install.lock marker. File operation. */
    case Finalize = 'finalize';
}
