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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Service;

class ServiceTest extends TestCase
{
    public function testCollidingFieldConstantsAreSkipped(): void
    {
        $camelCaseField = (new Input())->setName('productCategory');
        $snakeCaseField = (new Input())->setName('product_category');

        $code = Service::buildFieldConstantsCode($camelCaseField, $snakeCaseField);

        $this->assertSame(1, substr_count($code, 'public const FIELD_PRODUCT_CATEGORY'));
        $this->assertStringContainsString("public const FIELD_PRODUCT_CATEGORY = 'productCategory';", $code);
        $this->assertStringNotContainsString("public const FIELD_PRODUCT_CATEGORY = 'product_category';", $code);
    }

    public function testNonCollidingFieldConstantsArePreserved(): void
    {
        $categoryField = (new Input())->setName('productCategory');
        $nameField = (new Input())->setName('productName');

        $code = Service::buildFieldConstantsCode($categoryField, $nameField);

        $this->assertStringContainsString("public const FIELD_PRODUCT_CATEGORY = 'productCategory';", $code);
        $this->assertStringContainsString("public const FIELD_PRODUCT_NAME = 'productName';", $code);
    }

    public function testLocalizedAndNonLocalizedFieldsShareConstantNames(): void
    {
        $camelCaseField = (new Input())->setName('productCategory');
        $snakeCaseField = (new Input())->setName('product_category');
        $localizedFields = new Localizedfields();
        $localizedFields->setFieldDefinitions([$snakeCaseField]);

        $code = Service::buildFieldConstantsCode($camelCaseField, $localizedFields);

        $this->assertSame(1, substr_count($code, 'public const FIELD_PRODUCT_CATEGORY'));
        $this->assertStringContainsString("public const FIELD_PRODUCT_CATEGORY = 'productCategory';", $code);
        $this->assertStringNotContainsString("public const FIELD_PRODUCT_CATEGORY = 'product_category';", $code);

        $code = Service::buildFieldConstantsCode($localizedFields, $camelCaseField);

        $this->assertSame(1, substr_count($code, 'public const FIELD_PRODUCT_CATEGORY'));
        $this->assertStringContainsString("public const FIELD_PRODUCT_CATEGORY = 'product_category';", $code);
        $this->assertStringNotContainsString("public const FIELD_PRODUCT_CATEGORY = 'productCategory';", $code);
    }

    public function testCollidingLocalizedFieldConstantsAreSkipped(): void
    {
        $camelCaseField = (new Input())->setName('productCategory');
        $snakeCaseField = (new Input())->setName('product_category');
        $nameField = (new Input())->setName('productName');
        $localizedFields = new Localizedfields();
        $localizedFields->setFieldDefinitions([$camelCaseField, $snakeCaseField, $nameField]);

        $code = Service::buildFieldConstantsCode($localizedFields);

        $this->assertSame(1, substr_count($code, 'public const FIELD_PRODUCT_CATEGORY'));
        $this->assertStringContainsString("public const FIELD_PRODUCT_CATEGORY = 'productCategory';", $code);
        $this->assertStringNotContainsString("public const FIELD_PRODUCT_CATEGORY = 'product_category';", $code);
        $this->assertStringContainsString("public const FIELD_PRODUCT_NAME = 'productName';", $code);
    }
}
