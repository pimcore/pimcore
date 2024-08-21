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

use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\ExecutionContextNotDefinedException;

/**
 * @internal
 */
final class ExecutionContext implements ExecutionContextInterface
{
    public function __construct(
        private readonly array $contexts
    ) {
    }

    public function getTranslationDomain(string $context): string
    {
        $this->validateContext($context);

        return $this->contexts[$context]['translations_domain'];
    }

    public function getErrorHandlingFromContext(string $context): ?string
    {
        $this->validateContext($context);

        return $this->contexts[$context]['error_handling'] ?? null;
    }

    private function validateContext(string $context): void
    {
        if (!isset($this->contexts[$context])) {
            throw new ExecutionContextNotDefinedException(
                sprintf('Execution context "%s" is not defined.', $context)
            );
        }
    }
}
