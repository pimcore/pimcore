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

namespace Pimcore\Tests\Unit\FeatureToggles\Fixtures;

use Pimcore\FeatureToggles\Feature;

/**
 * @method static ValidFeature FLAG_0()
 * @method static ValidFeature FLAG_1()
 * @method static ValidFeature FLAG_2()
 * @method static ValidFeature FLAG_3()
 */
class ValidFeature extends Feature
{
    const FLAG_0 = 1;
    const FLAG_1 = 2;
    const FLAG_2 = 4;
    const FLAG_3 = 8;

    public static function getType(): string
    {
        return 'valid_feature';
    }
}

class ValidFeatureMaximumFlags extends Feature
{
    const FLAG_0  = 1;
    const FLAG_1  = 2;
    const FLAG_2  = 4;
    const FLAG_3  = 8;
    const FLAG_4  = 16;
    const FLAG_5  = 32;
    const FLAG_6  = 64;
    const FLAG_7  = 128;
    const FLAG_8  = 256;
    const FLAG_9  = 512;
    const FLAG_10 = 1024;
    const FLAG_11 = 2048;
    const FLAG_12 = 4096;
    const FLAG_13 = 8192;
    const FLAG_14 = 16384;
    const FLAG_15 = 32768;
    const FLAG_16 = 65536;
    const FLAG_17 = 131072;
    const FLAG_18 = 262144;
    const FLAG_19 = 524288;
    const FLAG_20 = 1048576;
    const FLAG_21 = 2097152;
    const FLAG_22 = 4194304;
    const FLAG_23 = 8388608;
    const FLAG_24 = 16777216;
    const FLAG_25 = 33554432;
    const FLAG_26 = 67108864;
    const FLAG_27 = 134217728;
    const FLAG_28 = 268435456;
    const FLAG_29 = 536870912;
    const FLAG_30 = 1073741824;

    public static function getType(): string
    {
        return 'valid_feature_maximum_flags';
    }
}

class InvalidFeatureRedefined0 extends Feature
{
    const INVALID = 0;
    const FLAG_0  = 1;
    const FLAG_1  = 2;

    public static function getType(): string
    {
        return 'invalid_feature_redefined_0';
    }
}

class InvalidFeatureRedefinedNone extends Feature
{
    const FLAG_0 = 1;
    const FLAG_1 = 2;
    const NONE   = 4;

    public static function getType(): string
    {
        return 'invalid_feature_redefined_none';
    }
}

class InvalidFeatureInvalidValue extends Feature
{
    const FLAG_0 = 1;
    const FLAG_1 = 2;
    const FLAG_2 = 4;
    const FLAG_3 = 5;

    public static function getType(): string
    {
        return 'invalid_feature_invalid_value';
    }
}

class InvalidFeatureDuplicateValue extends Feature
{
    const FLAG_0 = 1;
    const FLAG_1 = 2;
    const FLAG_2 = 4;
    const FLAG_3 = 4;

    public static function getType(): string
    {
        return 'invalid_feature_duplicate_value';
    }
}

class InvalidFeatureExceedMaximumFlags extends ValidFeatureMaximumFlags
{
    const FLAG_31 = 2147483648;

    public static function getType(): string
    {
        return 'invalid_feature_exceed_maximum_flags';
    }
}
