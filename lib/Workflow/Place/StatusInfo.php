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

namespace Pimcore\Workflow\Place;

use Pimcore\Workflow\Manager;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class StatusInfo
{
    private Manager $workflowManager;

    private Environment $twig;

    private TranslatorInterface $translator;

    private string $userLanguage;

    public function __construct(Manager $workflowManager, Environment $twig, TranslatorInterface $translator)
    {
        $this->workflowManager = $workflowManager;
        $this->twig = $twig;
        $this->translator = $translator;

        $user = \Pimcore\Tool\Admin::getCurrentUser();
        $this->userLanguage = $user ? $user->getLanguage() : 'en';
    }

    public function getToolbarHtml(object $subject): string
    {
        $places = $this->getAllPlaces($subject, true);

        return $this->twig->render(
            '@PimcoreCore/Workflow/statusinfo/toolbarStatusInfo.html.twig',
            [
                'places' => $places,
                'translator' => $this->translator,
                'lang' => $this->userLanguage,
            ]
        );
    }

    public function getAllPalacesHtml(object $subject, string $workflowName = null): string
    {
        $places = $this->getAllPlaces($subject, false, $workflowName);

        return $this->twig->render(
            '@PimcoreCore/Workflow/statusinfo/allPlacesStatusInfo.html.twig',
            [
                'places' => $places,
                'translator' => $this->translator,
                'lang' => $this->userLanguage,
            ]
        );
    }

    public function getAllPlacesForCsv(object $subject, string $workflowName = null): string
    {
        $places = $this->getAllPlaces($subject, false, $workflowName);
        $result = [];

        foreach ($places as $place) {
            $result[] = $place->getLabel();
        }

        return implode(', ', $result);
    }

    /**
     * @return PlaceConfig[]
     */
    private function getAllPlaces(object $subject, bool $visibleInHeaderOnly = false, string $workflowName = null): array
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
