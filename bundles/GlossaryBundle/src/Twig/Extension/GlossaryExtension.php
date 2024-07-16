<?php

declare(strict_types=1);

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
        if (!$string) {
            return $string;
        }

        return $this->glossaryProcessor->process($string, $options);
    }
}
