<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
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
        if (!isset($this->contexts[$context])) {
            throw new ExecutionContextNotDefinedException(
                sprintf('Execution context "%s" is not defined.', $context)
            );
        }

        return $this->contexts[$context]['translations_domain'];
    }

}
