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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Model;

use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;

final class JobStep implements JobStepInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $messageFQCN,
        private readonly string $condition,
        private readonly array $config,
        private readonly SelectionProcessingMode $selectionProcessingMode = SelectionProcessingMode::FOR_EACH,
        private JobStepStates $jobStepState = JobStepStates::NOT_STARTED,
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

    public function getState(): JobStepStates
    {
        return $this->jobStepState;
    }

    public function setState(JobStepStates $state): void
    {
        $this->jobStepState = $state;
    }
}
