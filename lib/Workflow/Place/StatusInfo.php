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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Workflow\Place;

use Pimcore\Workflow\Manager;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StatusInfo
{
    /**
     * @var Manager
     */
    private $workflowManager;

    /**
     * @var EngineInterface $templatingEngine
     */
    private $templatingEngine;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(Manager $workflowManager, EngineInterface $templatingEngine, TranslatorInterface $translator)
    {
        $this->workflowManager = $workflowManager;
        $this->templatingEngine = $templatingEngine;
        $this->translator = $translator;
    }

    public function getToolbarHtml($subject): string
    {
        $places = $this->getAllPlaces($subject, true);

        return $this->templatingEngine->render(
            '@PimcoreCore/Workflow/statusinfo/toolbarStatusInfo.html.twig',
            [
                'places' => $places,
                'translator' => $this->translator,
            ]
        );
    }

    public function getAllPalacesHtml($subject, string $workflowName = null): string
    {
        $places = $this->getAllPlaces($subject, false, $workflowName);

        return $this->templatingEngine->render(
            '@PimcoreCore/Workflow/statusinfo/allPlacesStatusInfo.html.twig',
            [
                'places' => $places,
                'translator' => $this->translator,
            ]
        );
    }

    public function getAllPlacesForCsv($subject, string $workflowName = null): string
    {
        $places = $this->getAllPlaces($subject, false, $workflowName);
        $result = [];

        foreach ($places as $place) {
            $result[] = $place->getLabel();
        }

        return implode(', ', $result);
    }

    /**
     * @param object $subject
     * @param bool $visibleInHeaderOnly
     *
     * @return PlaceConfig[]
     */
    private function getAllPlaces($subject, bool $visibleInHeaderOnly = false, string $workflowName = null): array
    {
        $places = [];

        foreach ($this->workflowManager->getAllWorkflowsForSubject($subject) as $workflow) {
            if (!is_null($workflowName) && $workflow->getName() != $workflowName) {
                continue;
            }

            $marking = $workflow->getMarking($subject);
            foreach ($this->workflowManager->getOrderedPlaceConfigs($workflow, $marking) as $place) {
                if (!$visibleInHeaderOnly || $place->isVisibleInHeader()) {
                    $places[] = $place;
                }
            }
        }

        return $this->filterPlaces($places);
    }

    /**
     * Multiple parallel workflows with the same places should not result in multiple status labels
     *
     * @param PlaceConfig[] $places
     *
     * @return PlaceConfig[]
     */
    protected function filterPlaces(array $places): array
    {
        $uniquePlaces = [];
        foreach ($places as $place) {
            $uniquePlaces[$place->getPlace()] = $place;
        }

        return array_values($uniquePlaces);
    }
}
