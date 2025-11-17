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

namespace Pimcore\Twig;

use Pimcore\Model\Document\Editable;
use Symfony\Bundle\TwigBundle\DependencyInjection\Configurator\EnvironmentConfigurator;
use Twig\Environment;
use Twig\Runtime\EscaperRuntime;

/**
 * @internal
 */
final class TwigEnvironmentConfigurator
{
    public function __construct(
        private readonly EnvironmentConfigurator $decorated,
    ) {
    }

    public function configure(Environment $environment): void
    {
        $this->decorated->configure($environment);

        $environment->getRuntime(EscaperRuntime::class)->addSafeClass(Editable::class, ['html']);
    }
}
