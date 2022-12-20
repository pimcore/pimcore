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

namespace Pimcore\DataObject\GridColumnConfig\Operator;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Place\StatusInfo;

/**
 * @internal
 */
final class WorkflowState extends AbstractOperator
{
    private StatusInfo $statusInfo;

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): \Pimcore\DataObject\GridColumnConfig\ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $context = $this->getContext();
        $purpose = $context['purpose'] ?? null;

        if ($purpose === 'gridview') {
            $result->value = $this->statusInfo->getAllPalacesHtml($element);
        } else {
            $result->value = $this->statusInfo->getAllPlacesForCsv($element);
        }

        return $result;
    }

    /**
     * @param StatusInfo $statusInfo
     *
     * @required
     */
    public function setWorkflowStatusInfo(StatusInfo $statusInfo)
    {
        $this->statusInfo = $statusInfo;
    }
}
