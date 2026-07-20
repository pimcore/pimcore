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

interface JobStepInterface
{
    public function getName(): string;

    public function getMessageFQCN(): string;

    public function getConfig(): array;

    public function getCondition(): string;

    public function getSelectionProcessingMode(): SelectionProcessingMode;
}
