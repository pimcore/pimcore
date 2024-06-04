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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Configuration;

use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @internal
 */
trait ValidateStepConfigurationTrait
{
    protected OptionsResolver $stepConfiguration;

    public function configurationIsValid(array $config): bool
    {
        try {
            $this->resolveStepConfiguration($config);
        } catch (Exception) {
            return false;
        }

        return true;
    }

    protected function configureStep(): void
    {
        // not configured should be configured in the usage.
    }

    /**
     * @throws Exception
     */
    private function resolveStepConfiguration(array $config): array
    {
        return $this->stepConfiguration->resolve($config);
    }
}
