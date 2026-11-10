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

namespace Pimcore\Tests\Support\Helper\ClassificationStore;

use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\ReferenceTranslatorInterface;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\TranslationReport;

/**
 * A reference translator of another bundle, as far as the container is concerned: it is registered as a
 * plain service in the test application's `services.yaml` and carries no tag of its own, so it reaches
 * `pimcore:classificationstore:translate-identifiers` only through the autoconfiguration
 * {@see \Pimcore\Bundle\CoreBundle\DependencyInjection\PimcoreCoreExtension} registers for
 * {@see ReferenceTranslatorInterface}.
 *
 * Used by {@see \Pimcore\Tests\Model\DataType\ClassificationStore\TranslateIdentifiersCommandTest}.
 *
 * @internal
 */
final class StubReferenceTranslator implements ReferenceTranslatorInterface
{
    public int $calls = 0;

    public ?bool $dryRun = null;

    public function getName(): string
    {
        return 'Stub surface';
    }

    public function translate(bool $dryRun): TranslationReport
    {
        $this->calls++;
        $this->dryRun = $dryRun;

        return new TranslationReport(0);
    }
}
