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

namespace Pimcore\Tests\Unit\CustomReportsBundle\Controller\Reports;

use Exception;
use Pimcore\Bundle\CustomReportsBundle\Controller\Reports\CustomReportController;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Model\User;
use Pimcore\Security\User\User as UserProxy;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class CustomReportControllerTest extends TestCase
{
    /**
     * Builds a controller instance whose getPimcoreUser() is stubbed to
     * return a fake user with the given id, without needing a real
     * authenticated security context.
     */
    private function controllerAsUser(int $userId): CustomReportController
    {
        $user = new User();
        $user->setId($userId);
        $userProxy = new UserProxy($user);

        return new class($userProxy) extends CustomReportController {
            public function __construct(private readonly UserProxy $fakeUser)
            {
            }

            protected function getPimcoreUser(bool $proxyUser = false): UserProxy|User|null
            {
                return $this->fakeUser;
            }

            public function resolveExportFile(string $exportFileName): string
            {
                return $this->getTemporaryFileFromFileName($exportFileName);
            }

            /**
             * @param array<string, mixed> $data
             */
            public function applyConfiguration(Config $report, array $data): void
            {
                $this->applyReportConfiguration($report, $data);
            }

            public function columnConfigurationErrorMessage(Exception $e): string
            {
                return $this->getColumnConfigurationErrorMessage($e);
            }
        };
    }

    public function testOwnerCanResolveTheirOwnExportFile(): void
    {
        $controller = $this->controllerAsUser(42);

        $resolved = $controller->resolveExportFile('report-export-42-abc123.csv');

        $this->assertStringEndsWith('/report-export-42-abc123.csv', $resolved);
    }

    public function testOtherUserCannotResolveSomeoneElsesExportFile(): void
    {
        // Before this fix, any user with the generic 'reports' permission could
        // resolve (and, via downloadCsvAction(), read and delete) an export file
        // created by a different user purely by knowing its filename.
        $controller = $this->controllerAsUser(99);

        $this->expectException(AccessDeniedHttpException::class);
        $controller->resolveExportFile('report-export-42-abc123.csv');
    }

    public function testFilenameNotMatchingTheExportPatternIsRejected(): void
    {
        $controller = $this->controllerAsUser(42);

        $this->expectException(AccessDeniedHttpException::class);
        $controller->resolveExportFile('not-an-export-file.csv');
    }

    public function testUpdateAppliesWhitelistedConfigurationFields(): void
    {
        $controller = $this->controllerAsUser(1);
        $report = new Config();

        $controller->applyConfiguration($report, [
            'sql' => 'SELECT 1',
            'niceName' => 'Quarterly figures',
            'shareGlobally' => false,
            'sharedUserNames' => ['alice'],
        ]);

        $this->assertSame('SELECT 1', $report->getSql());
        $this->assertSame('Quarterly figures', $report->getNiceName());
        $this->assertFalse($report->getShareGlobally());
        $this->assertSame(['alice'], $report->getSharedUserNames());
    }

    public function testUpdateIgnoresFieldsOutsideTheWhitelist(): void
    {
        // Before this fix, updateAction() applied any setter whose name matched an
        // attacker-supplied key, letting a reports_config user tamper with a report's
        // identity (name) and audit timestamps by adding extra keys to the payload.
        $controller = $this->controllerAsUser(1);
        $report = new Config();
        $report->setName('finance_report');
        $report->setCreationDate(1000);
        $report->setModificationDate(1000);

        $controller->applyConfiguration($report, [
            'name' => 'attacker_renamed',
            'creationDate' => 0,
            'modificationDate' => 0,
            'niceName' => 'legit change',
        ]);

        $this->assertSame('finance_report', $report->getName());
        $this->assertSame(1000, $report->getCreationDate());
        $this->assertSame(1000, $report->getModificationDate());
        $this->assertSame('legit change', $report->getNiceName());
    }

    public function testColumnConfigurationErrorMessageDoesNotLeakExceptionDetails(): void
    {
        // Before this fix, columnConfigAction() returned the raw exception message to the
        // client, leaking DB error output (schema name, table names, MySQL version, paths).
        $controller = $this->controllerAsUser(1);
        $sensitive = "SQLSTATE[42S02]: Base table or view not found: 1146 "
            . "Table 'pimcore.secret_probe_xyz' doesn't exist";

        $message = $controller->columnConfigurationErrorMessage(new Exception($sensitive));

        $this->assertSame('An error occurred while loading the column configuration.', $message);
        $this->assertStringNotContainsString('SQLSTATE', $message);
        $this->assertStringNotContainsString('pimcore.secret_probe_xyz', $message);
    }
}
