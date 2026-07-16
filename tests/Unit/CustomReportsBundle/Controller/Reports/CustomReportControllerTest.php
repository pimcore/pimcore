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

use Pimcore\Bundle\CustomReportsBundle\Controller\Reports\CustomReportController;
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
}
