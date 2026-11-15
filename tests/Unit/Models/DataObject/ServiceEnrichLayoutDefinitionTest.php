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

namespace Pimcore\Tests\Unit\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for PEES-1063: GridView ignored a role's "Viewable Languages" /
 * "Editable Languages" workspace restriction because Service::enrichLayoutDefinition()
 * skipped permission enrichment whenever the caller passed context['purpose'] === 'gridconfig',
 * even though the same layout enrichment is reused to build the GridView column configuration.
 *
 * @see https://pimcore.atlassian.net/browse/PEES-1063
 *
 * @group model.dataobject.service
 */
class ServiceEnrichLayoutDefinitionTest extends TestCase
{
    private function buildObjectWithLanguagePermissions(array $lView, array $lEdit): Concrete
    {
        return new class($lView, $lEdit) extends Concrete {
            public function __construct(private readonly array $lView, private readonly array $lEdit)
            {
            }

            public function getPermissions(?string $type = null, ?User $user = null, bool $quote = true): ?array
            {
                return match ($type) {
                    'lView' => ['lView' => implode(',', $this->lView)],
                    'lEdit' => ['lEdit' => implode(',', $this->lEdit)],
                    default => null,
                };
            }
        };
    }

    /**
     * Before the fix: passing context['purpose'] = 'gridconfig' skipped enrichLayoutPermissions()
     * entirely, so permissionView/permissionEdit stayed null and the GridView showed every language.
     */
    public function testGridConfigPurposeNoLongerBypassesLanguagePermissions(): void
    {
        $object = $this->buildObjectWithLanguagePermissions(['en', 'de'], ['en']);

        $user = new User();
        $user->setAdmin(false);

        $layout = new Localizedfields();

        Service::enrichLayoutDefinition($layout, $object, ['purpose' => 'gridconfig'], $user);

        self::assertNotNull(
            $layout->getPermissionView(),
            'permissionView must be enforced for gridconfig purpose, not left unrestricted'
        );
        self::assertSame(['en', 'de'], $layout->getPermissionView());
        self::assertSame(['en'], $layout->getPermissionEdit());
    }

    /**
     * Admins are exempt from language restrictions, with or without a gridconfig purpose.
     */
    public function testAdminUserIsNotRestricted(): void
    {
        $object = $this->buildObjectWithLanguagePermissions(['en'], ['en']);

        $user = new User();
        $user->setAdmin(true);

        $layout = new Localizedfields();

        Service::enrichLayoutDefinition($layout, $object, ['purpose' => 'gridconfig'], $user);

        self::assertNull($layout->getPermissionView());
        self::assertNull($layout->getPermissionEdit());
    }

    /**
     * With no permission subject at all - neither the $object argument nor context['object'] -
     * enrichment still cannot run (workspace permissions are resolved per-object). This is
     * unchanged by the fix.
     */
    public function testNoPermissionSubjectMeansNoEnrichmentRegardlessOfPurpose(): void
    {
        $user = new User();
        $user->setAdmin(false);

        $layout = new Localizedfields();

        Service::enrichLayoutDefinition($layout, null, ['purpose' => 'gridconfig'], $user);

        self::assertNull($layout->getPermissionView());
        self::assertNull($layout->getPermissionEdit());
    }

    /**
     * Grid column configuration cannot always supply a Concrete $object (field-level enrichers
     * require that), but can still hand the real permission subject through context['object'].
     * enrichLayoutDefinition() must fall back to that instead of silently dropping it - this is
     * the shape callers like the classic GridView column config controller actually use: $object
     * is null, and the object lives in context['object'].
     */
    public function testNullObjectWithContextObjectStillEnforcesLanguagePermissions(): void
    {
        $object = $this->buildObjectWithLanguagePermissions(['en', 'de'], ['en']);

        $user = new User();
        $user->setAdmin(false);

        $layout = new Localizedfields();

        Service::enrichLayoutDefinition(
            $layout,
            null,
            ['purpose' => 'gridconfig', 'object' => $object],
            $user
        );

        self::assertSame(['en', 'de'], $layout->getPermissionView());
        self::assertSame(['en'], $layout->getPermissionEdit());
    }
}
