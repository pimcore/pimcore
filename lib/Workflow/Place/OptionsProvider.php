<?php

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

namespace Pimcore\Workflow\Place;

use Pimcore\Helper\ContrastColor;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\MarkingStore\DataObjectSplittedStateMarkingStore;
use Symfony\Contracts\Translation\TranslatorInterface;

class OptionsProvider implements SelectOptionsProviderInterface
{
    /**
     * @var Manager
     */
    private $workflowManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(Manager $workflowManager, TranslatorInterface $translator)
    {
        $this->workflowManager = $workflowManager;
        $this->translator = $translator;
    }

    /**
     * @param array $context
     * @param Select|Multiselect $fieldDefinition
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getOptions($context, $fieldDefinition)
    {
        $workflowName = $fieldDefinition->getOptionsProviderData();
        if (!$workflowName) {
            throw new \Exception('setup workflow name as options provider data');
        }

        $options = [];

        $workflow = $this->workflowManager->getWorkflowByName($workflowName);

        $mappedPlaces = null;
        $markingStore = $workflow->getMarkingStore();

        if ($markingStore instanceof DataObjectSplittedStateMarkingStore) {
            $mappedPlaces = $markingStore->getMappedPlaces($fieldDefinition->getName());
        }

        foreach ($this->workflowManager->getPlaceConfigsByWorkflowName($workflowName) as $placeConfig) {
            if (!is_array($mappedPlaces) || in_array($placeConfig->getPlace(), $mappedPlaces)) {
                $options[] = [
                    'key' => $this->generatePlaceLabel($placeConfig),
                    'value' => $placeConfig->getPlace(),
                ];
            }
        }

        return $options;
    }

    protected function generatePlaceLabel(PlaceConfig $placeConfig): string
    {
        if (!method_exists($this->translator, 'getLocale')) {
            return '';
        }
        // do not translate or format options when not in admin context
        if (empty($this->translator->getLocale())) {
            return $placeConfig->getLabel();
        }

        // disabled for the moment
        return sprintf('<div class="pimcore-workflow-place-indicator" style="background-color: %s; color:%s">%s</div>',
            $placeConfig->getColor(),
            ContrastColor::getContrastColor($placeConfig->getColor()),
            $this->translator->trans($placeConfig->getLabel(), [], 'admin')
        );
    }

    /**
     * @param array $context
     * @param Data $fieldDefinition
     *
     * @return bool
     */
    public function hasStaticOptions($context, $fieldDefinition)
    {
        return true;
    }

    /**
     * @param array $context
     * @param Data $fieldDefinition
     * @return null
     */
    public function getDefaultValue($context, $fieldDefinition)
    {
        return null;
    }
}
