<?php

declare(strict_types=1);

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

namespace Pimcore\Event\Tracking\Piwik;

use Pimcore\Analytics\Tracking\Piwik\Dto\ReportConfig;
use Symfony\Component\EventDispatcher\Event;

class ReportConfigEvent extends Event
{
    /**
     * @var ReportConfig[]
     */
    private $reports = [];

    /**
     * @param ReportConfig[] $reports
     */
    public function __construct(array $reports)
    {
        $this->setReports($reports);
    }

    /**
     * @return ReportConfig[]
     */
    public function getReports(): array
    {
        return $this->reports;
    }

    public function setReports(array $reports)
    {
        $this->reports = [];

        foreach ($reports as $report) {
            $this->addReport($report);
        }
    }

    public function addReport(ReportConfig $report)
    {
        $this->reports[] = $report;
    }
}
