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
