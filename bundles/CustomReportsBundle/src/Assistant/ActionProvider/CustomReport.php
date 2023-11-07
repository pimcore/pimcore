<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\CustomReportsBundle\Assistant\ActionProvider;

use Pimcore\Bundle\AssistantBundle\Assistant\ActionProviderInterface;
use Pimcore\Bundle\AssistantBundle\Assistant\Model\ActionContextInterface;
use Pimcore\Bundle\AssistantBundle\Assistant\Model\DefaultAction;
use Pimcore\Bundle\CustomReportsBundle\Tool;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolver;

/**
 * @internal
 */
final class CustomReport implements ActionProviderInterface
{
    public function __construct(
        private readonly UserResolver $userResolver
    ) {
    }

    public function findRelevantActionsFirstLevel(
        string $searchToken, ActionContextInterface $context, string $searchLanguage
    ): array {
        return [];
    }

    public function findRelevantActionsSecondLevel(
        string $searchToken, ActionContextInterface $context, string $searchLanguage
    ): array {

        $items = $this->getReportsForGivenUser($context->getUsername());

        return $this->filterReports($items, $searchToken);
    }

    public function isAllowedForContext(ActionContextInterface $context): bool
    {
        return true;
    }

    private function filterReports(array $reports, string $searchToken): array
    {
        $filteredReports = [];

        foreach ($reports as $report) {
            if($report->getDataSourceConfig() !== null &&
                preg_grep("#$searchToken.*#i", [$report->getName(), $report->getNiceName()])) {

                $iconClass = 'pimcore_icon_custom_report_default';
                if ($report->getIconClass()) {
                    $iconClass = htmlspecialchars($report->getIconClass());
                }

                $filteredReports[] = new DefaultAction(
                    'Open ' . htmlspecialchars($report->getName()),
                    'customReportsAdapter',
                    [
                        'name' => htmlspecialchars($report->getName()),
                        'niceName' => htmlspecialchars($report->getNiceName()),
                        'iconClass' => htmlspecialchars($report->getIconClass()),
                        'group' => htmlspecialchars($report->getGroup()),
                        'groupIconClass' => htmlspecialchars($report->getGroupIconClass()),
                        'menuShortcut' => $report->getMenuShortcut(),
                        'reportClass' => $report->getReportClass() ? htmlspecialchars($report->getReportClass()) :
                            'pimcore.bundle.customreports.custom.report',
                    ],
                    $iconClass
                );
            }
        }

        return $filteredReports;
    }

    private function getReportsForGivenUser(string $userName): array
    {
        $list = new Tool\Config\Listing();

        return $list->getDao()->loadForGivenUser(
            $this->userResolver->getByName($userName)
        );
    }
}
