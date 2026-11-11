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

namespace Pimcore\Model\DataObject\Data\Link;

use Pimcore\Model\Document\Editable\Link\AttributeSanitizer;

/**
 * Holds the AttributeSanitizer policy applied by \Pimcore\Model\DataObject\Data\Link::getHtml()
 * (GHSA-h78x-47qg-qjmq). It is independent of the policy of the Document Link editable.
 *
 * The default is fully permissive and keeps the historical output. To opt into the strict policy,
 * set the "pimcore.objects.link_sanitizer.strict" config option to true - PimcoreCoreBundle::boot()
 * reads it and installs a policy built from it. For a fully custom policy, call setInstance()
 * directly instead, from your own bundle's boot() method.
 */
final class SanitizerPolicy
{
    private static ?AttributeSanitizer $instance = null;

    /**
     * Tracks explicit setInstance() calls separately from $instance, because $instance is also
     * populated by getInstance()'s lazy permissive fallback.
     */
    private static bool $explicitlyConfigured = false;

    public static function getInstance(): AttributeSanitizer
    {
        return self::$instance ??= new AttributeSanitizer();
    }

    /**
     * Installs a custom policy, most commonly AttributeSanitizer::strict(). Pass null to reset.
     */
    public static function setInstance(?AttributeSanitizer $sanitizer): void
    {
        self::$instance = $sanitizer;
        self::$explicitlyConfigured = $sanitizer !== null;
    }

    /**
     * True once something has explicitly called setInstance() with a non-null policy - never true
     * merely because getInstance() was called.
     */
    public static function isConfigured(): bool
    {
        return self::$explicitlyConfigured;
    }
}
