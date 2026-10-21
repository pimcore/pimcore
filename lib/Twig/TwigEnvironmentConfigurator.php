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

use Closure;
use InvalidArgumentException;
use Pimcore\Model\Document\Editable;
use Symfony\Bundle\TwigBundle\DependencyInjection\Configurator\EnvironmentConfigurator;
use Twig\Environment;
use Twig\Runtime\EscaperRuntime;

/**
 * Decorates the `twig.configurator.environment` service to mark editables as safe for the "html" escaping strategy.
 *
 * Other bundles decorate the same service and type the inner service against Symfony's
 * EnvironmentConfigurator (e.g. symfony/ux-twig-component), so this class extends it to be accepted
 * as inner service by them. In turn, their decorators do not extend it either, so any object exposing
 * `configure(Environment): void` is accepted as inner service here, whichever decoration order the
 * container ends up with.
 *
 * @internal
 */
final class TwigEnvironmentConfigurator extends EnvironmentConfigurator
{
    private readonly Closure $configureDecorated;

    public function __construct(object $decorated)
    {
        if (!method_exists($decorated, 'configure')) {
            throw new InvalidArgumentException(sprintf(
                'The decorated twig environment configurator must provide a configure() method, %s given.',
                get_debug_type($decorated)
            ));
        }

        $this->configureDecorated = $decorated->configure(...);
    }

    public function configure(Environment $environment): void
    {
        ($this->configureDecorated)($environment);

        $environment->getRuntime(EscaperRuntime::class)->addSafeClass(Editable::class, ['html']);
    }
}
