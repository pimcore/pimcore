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

namespace Pimcore\Bundle\NewsletterBundle;

use Pimcore\Bundle\AdminBundle\Support\BundleAdminSupportTrait;
use Pimcore\Bundle\AdminBundle\Support\PimcoreBundleAdminSupportInterface;
use Pimcore\Bundle\NewsletterBundle\DependencyInjection\Compiler\CustomReportsPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PimcoreNewsletterBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminSupportInterface
{
    use BundleAdminSupportTrait;
    use PackageVersionTrait;

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorenewsletter/js/startup.js',
            '/bundles/pimcorenewsletter/js/document/newsletter.js',
            '/bundles/pimcorenewsletter/js/document/newsletters/settings.js',
            '/bundles/pimcorenewsletter/js/document/newsletters/sendingPanel.js',
            '/bundles/pimcorenewsletter/js/document/newsletters/plaintextPanel.js',
            '/bundles/pimcorenewsletter/js/document/newsletters/addressSourceAdapters/default.js',
            '/bundles/pimcorenewsletter/js/document/newsletters/addressSourceAdapters/csvList.js',
            '/bundles/pimcorenewsletter/js/document/newsletters/addressSourceAdapters/report.js',
            '/bundles/pimcorenewsletter/js/object/classes/data/newsletterActive.js',
            '/bundles/pimcorenewsletter/js/object/classes/data/newsletterConfirmed.js',
            '/bundles/pimcorenewsletter/js/object/tags/newsletterActive.js',
            '/bundles/pimcorenewsletter/js/object/tags/newsletterConfirmed.js',
        ];
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcorenewsletter/css/icons.css',
        ];
    }

    public function getEditmodeJsPaths(): array
    {
        return [];
    }

    public function getEditmodeCssPaths(): array
    {
        return [];
    }

    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CustomReportsPass());
    }
}
