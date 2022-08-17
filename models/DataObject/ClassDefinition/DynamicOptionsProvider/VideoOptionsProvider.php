<?php

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

namespace Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider;

use Pimcore\Model\DataObject\ClassDefinition\Data;

class VideoOptionsProvider implements MultiSelectOptionsProviderInterface
{

    public const TYPE_ASSET = 'asset';
    public const TYPE_YOUTUBE = 'youtube';
    public const TYPE_VIMEO = 'vimeo';
    public const TYPE_DAILYMOTION = 'dailymotion';

    /**
     * {@inheritdoc}
     */
    public function getOptions($context, $fieldDefinition)
    {
        return [
            self::TYPE_ASSET,
            self::TYPE_YOUTUBE,
            self::TYPE_VIMEO,
            self::TYPE_DAILYMOTION,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function hasStaticOptions($context, $fieldDefinition)
    {
        return true;
    }

    public function getDefaultValue($context, $fieldDefinition)
    {
        // TODO: Implement getDefaultValue() method.
    }
}
