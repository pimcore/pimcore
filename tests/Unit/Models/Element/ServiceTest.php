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

namespace Pimcore\Tests\Unit\Models\Element;

use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for pimcore/internal-improvements#16 — the role-workspaces query in
 * findForbiddenPaths() used to SELECT `userid` alongside `GROUP BY cpath` without aggregating
 * it, which is invalid under MySQL 8's default ONLY_FULL_GROUP_BY sql_mode (userid is
 * genuinely ambiguous per cpath — multiple roles/users can share a workspace path row). The
 * result's `userid` column was never read downstream, so it was simply dropped from the SELECT.
 *
 * This asserts against the source directly (rather than exercising the static method, which
 * would require a live DB connection) because the fix is a pure SQL-string change.
 */
class ServiceTest extends TestCase
{
    private function getRoleWorkspacesSqlSource(): string
    {
        $source = file_get_contents(PIMCORE_PROJECT_ROOT . '/models/Element/Service.php');

        if (!preg_match('/\$roleWorkspacesSql\s*=\s*\'([^\']*)\'/', $source, $matches)) {
            $this->fail('Could not locate the $roleWorkspacesSql assignment in models/Element/Service.php');
        }

        return $matches[1];
    }

    public function testRoleWorkspacesQueryDoesNotSelectUngroupedUserId(): void
    {
        $sql = $this->getRoleWorkspacesSqlSource();

        $this->assertMatchesRegularExpression('/^SELECT cpath, max\(list\) as list FROM users_workspaces_/', $sql);
        $this->assertDoesNotMatchRegularExpression('/^SELECT[^F]*\buserid\b/i', $sql);
    }
}
