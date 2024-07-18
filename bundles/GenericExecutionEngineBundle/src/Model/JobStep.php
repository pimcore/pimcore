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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Model;

use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;

final class JobStep implements JobStepInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $messageFQCN,
        private readonly string $condition,
        private readonly array $config,
        private readonly SelectionProcessingMode $selectionProcessingMode = SelectionProcessingMode::FOR_EACH
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMessageFQCN(): string
    {
        return $this->messageFQCN;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getCondition(): string
    {
        return $this->condition;
    }

    public function getSelectionProcessingMode(): SelectionProcessingMode
    {
        return $this->selectionProcessingMode;
    }
}
