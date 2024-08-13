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

interface JobStepInterface
{
    public function getName(): string;

    public function getMessageFQCN(): string;

    public function getConfig(): array;

    public function getCondition(): string;

    public function getSelectionProcessingMode(): SelectionProcessingMode;
}
