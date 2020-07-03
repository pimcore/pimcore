<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\GridColumnConfig\Operator;

use Pimcore\Workflow\Place\StatusInfo;

class WorkflowState extends AbstractOperator
{
    /**
     * @var StatusInfo
     */
    private $statusInfo;

    public function getLabeledValue($element)
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
     * @required
     */
    public function setWorkflowStatusInfo(StatusInfo $statusInfo)
    {
        $this->statusInfo = $statusInfo;
    }
}
