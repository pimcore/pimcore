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
