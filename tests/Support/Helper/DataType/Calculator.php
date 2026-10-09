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

namespace Pimcore\Tests\Support\Helper\DataType;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\ClassDefinition\CalculatorClassInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue;

class Calculator implements CalculatorClassInterface
{
    private const EVALUATION_COUNTER = 'modeltest.testCalculatedValue.evaluations';

    public static function resetEvaluationCount(): void
    {
        RuntimeCache::set(self::EVALUATION_COUNTER, 0);
    }

    public static function getEvaluationCount(): int
    {
        return RuntimeCache::isRegistered(self::EVALUATION_COUNTER) ? (int)RuntimeCache::get(self::EVALUATION_COUNTER) : 0;
    }

    public function compute(Concrete $object, CalculatedValue $context): string
    {
        RuntimeCache::set(self::EVALUATION_COUNTER, self::getEvaluationCount() + 1);

        $value = '';
        if (RuntimeCache::isRegistered('modeltest.testCalculatedValue.value')) {
            $value = RuntimeCache::get('modeltest.testCalculatedValue.value');
        }

        return $value;
    }

    public function getCalculatedValueForEditMode(Concrete $object, CalculatedValue $context): string
    {
    }
}
