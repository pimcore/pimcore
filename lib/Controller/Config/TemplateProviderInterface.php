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

namespace Pimcore\Controller\Config;

/**
 * Extension point for contributing additional selectable templates to the
 * template list built by {@see ControllerDataProvider::getTemplates()} (used
 * to configure the template for documents and static routes).
 *
 * The {@see ControllerDataProvider} builds its template list by scanning the
 * project's `templates/` directory and the template directories of non-core
 * bundles. Implement this interface to contribute templates that live outside
 * those scanned paths (e.g. dynamically generated ones).
 *
 * Implementations are collected by {@see ControllerDataProvider} via the
 * `pimcore.template_provider` service tag. CoreBundle service definitions may
 * receive this tag through autoconfiguration, but services defined outside of
 * that scope must add the `pimcore.template_provider` tag explicitly.
 */
interface TemplateProviderInterface
{
    /**
     * Returns additional selectable template names.
     *
     * Each entry must use the same notation as the values returned by
     * {@see ControllerDataProvider::getTemplates()}: either a bare,
     * project-relative path (e.g. `content/foo.html.twig`) or a
     * Twig-namespaced name (e.g. `@SomeNamespace/foo.html.twig`).
     *
     * @return string[]
     */
    public function getTemplates(): array;
}
