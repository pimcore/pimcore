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

namespace Pimcore\Model\DataObject\Classificationstore;

/**
 * Extension point for `pimcore:classificationstore:translate-identifiers`. A service implementing this interface
 * is tagged `pimcore.classificationstore.reference_translator` by autoconfiguration and executed by the command; it
 * rewrites the stored references to a group or key by integer id into the identifier form.
 *
 * An implementation must leave a reference it cannot translate untouched and report it, and must write nothing
 * when `$dryRun` is true while reporting what it would have written.
 *
 * This is a public extension point and therefore intentionally not `@internal`, and it lives here rather than
 * next to core's own translator for that reason: a bundle implementing it must not have to depend on a
 * bundle-internal namespace. Core's implementation
 * ({@see \Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\GridConfigReferenceTranslator}) stays
 * internal, exactly as {@see LabelResolverInterface} and its implementation do.
 */
interface ReferenceTranslatorInterface
{
    /**
     * Short name of the surface this translator covers, used as the heading of its report section.
     */
    public function getName(): string;

    public function translate(bool $dryRun): TranslationReport;
}
