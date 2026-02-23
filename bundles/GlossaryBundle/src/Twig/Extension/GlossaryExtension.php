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

namespace Pimcore\Bundle\GlossaryBundle\Twig\Extension;

use Pimcore\Bundle\GlossaryBundle\Tool\Processor;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @internal
 */
class GlossaryExtension extends AbstractExtension
{
    private Processor $glossaryProcessor;

    public function __construct(Processor $glossaryProcessor)
    {
        $this->glossaryProcessor = $glossaryProcessor;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('pimcore_glossary', [$this, 'applyGlossary'], ['is_safe' => ['html']]),
        ];
    }

    public function applyGlossary(string $string, array $options = []): string
    {
        trigger_deprecation(
            'pimcore/glossary-bundle',
            '12.3',
            'The GlossaryBundle Twig extension "pimcore_glossary" is deprecated and
             will be discontinued with Pimcore Studio.'
        );

        if (!$string) {
            return $string;
        }

        return $this->glossaryProcessor->process($string, $options);
    }
}
