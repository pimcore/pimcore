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

namespace Pimcore\Twig\Extension;

use Pimcore\Tool\Glossary\Processor;
use Pimcore\Twig\TokenParser\GlossaryTokenParser;
use Twig\Extension\AbstractExtension;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;

/**
 * @internal
 */
class GlossaryExtension extends AbstractExtension
{
    /**
     * @var Processor
     */
    private $glossaryProcessor;

    /**
     * @param Processor $glossaryProcessor
     *
     */
    public function __construct(Processor $glossaryProcessor)
    {
        $this->glossaryProcessor = $glossaryProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('pimcore_glossary', [$this, 'applyGlossary'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $string
     * @param array $options
     *
     * @return string
     */
    public function applyGlossary(string $string, array $options = []): string
    {
        if (empty($string) || !is_string($string)) {
            return $string;
        }

        return $this->glossaryProcessor->process($string, $options);
    }

    /**
     * @deprecated
     *
     * @return TokenParserInterface[]
     */
    public function getTokenParsers(): array
    {
        return [
            new GlossaryTokenParser(),
        ];
    }

    /**
     * @deprecated
     */
    public function start()
    {
        ob_start();
    }

    /**
     * @deprecated
     *
     * @param array $options
     */
    public function stop(array $options = [])
    {
        $contents = ob_get_clean();

        if (empty($contents) || !is_string($contents)) {
            $result = $contents;
        } else {
            $result = $this->glossaryProcessor->process($contents, $options);
        }

        echo $result;
    }
}
