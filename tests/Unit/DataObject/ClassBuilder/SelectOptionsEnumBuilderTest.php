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

namespace Pimcore\Tests\Unit\DataObject\ClassBuilder;

use PHPUnit\Framework\TestCase;
use Pimcore\DataObject\ClassBuilder\SelectOptionsEnumBuilder;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\DataObject\SelectOptions\Data\SelectOption;

/**
 * The enum generator escapes single quotes in option values/labels before embedding them in a
 * single-quoted PHP string literal, but previously left backslashes untouched. A value/label
 * ending in `\'` was therefore emitted as `\\'` in the generated source: an escaped backslash
 * followed by a live, unescaped closing quote, closing the string (and the enum body) early and
 * placing attacker-controlled tokens at file scope, where they execute on autoload
 * (GHSA-x347-3h85-7jh8).
 *
 * These tests exercise the real Config/SelectOption/SelectOptionsEnumBuilder::buildEnum() path
 * and use PHP's own tokenizer (token_get_all) to decode the generated string literals. Tokenizing
 * is inert (it never executes the generated source), so it safely distinguishes "value round-trips
 * intact" (fixed) from "value/literal was truncated by an early, unescaped closing quote"
 * (vulnerable) without ever require()-ing or eval()-ing attacker-controlled generated code.
 */
class SelectOptionsEnumBuilderTest extends TestCase
{
    public function testMaliciousOptionValueCannotBreakOutOfTheGeneratedCaseLiteral(): void
    {
        // Only double quotes are used in the "injected" tail, so a correct single-quote escaper
        // cannot interfere with it - isolating the missing backslash handling as the sole defect.
        $payload = '\\\'; } echo "___PIMCORE_RCE_PROOF___"; //';

        $source = $this->buildEnumSource(new SelectOption($payload, 'Safe label', 'MaliciousValue'));
        $strings = $this->extractConstantStrings($source);

        $this->assertSame(
            $payload,
            $strings[0] ?? null,
            'Option value must round-trip intact through the generated enum case literal'
        );
    }

    public function testMaliciousOptionLabelCannotBreakOutOfTheGeneratedLabelMatchLiteral(): void
    {
        $payload = '\\\'; } echo "___PIMCORE_RCE_PROOF___"; //';

        $source = $this->buildEnumSource(new SelectOption('safe-value', $payload, 'MaliciousLabel'));
        $strings = $this->extractConstantStrings($source);

        $this->assertSame(
            $payload,
            $strings[1] ?? null,
            'Option label must round-trip intact through the generated label match literal'
        );
    }

    /**
     * @dataProvider legitimateValueProvider
     */
    public function testLegitimateOptionValuesRoundTripUnchanged(string $value): void
    {
        $source = $this->buildEnumSource(new SelectOption($value, 'Some label', 'SomeName'));
        $strings = $this->extractConstantStrings($source);

        $this->assertSame($value, $strings[0] ?? null);
    }

    public static function legitimateValueProvider(): array
    {
        return [
            'plain value' => ['plain'],
            'single quote' => ["O'Brien"],
            'backslash not at the end' => ['C:\\Users\\test'],
            'trailing single backslash' => ['trailing\\'],
        ];
    }

    private function buildEnumSource(SelectOption $selectOption): string
    {
        $config = (new Config())
            ->setId('EnumBuilderTest')
            ->setSelectOptions($selectOption);

        return (new SelectOptionsEnumBuilder())->buildEnum($config);
    }

    /**
     * Tokenizes (never executes) the generated source and returns the decoded value of every
     * single-quoted string literal, in source order.
     *
     * @return string[]
     */
    private function extractConstantStrings(string $source): array
    {
        $values = [];
        foreach (token_get_all($source) as $token) {
            if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING && str_starts_with($token[1], "'")) {
                $values[] = $this->decodeSingleQuotedLiteral(substr($token[1], 1, -1));
            }
        }

        return $values;
    }

    private function decodeSingleQuotedLiteral(string $inner): string
    {
        $decoded = '';
        $length = strlen($inner);
        for ($i = 0; $i < $length; $i++) {
            if ($inner[$i] === '\\' && $i + 1 < $length && ($inner[$i + 1] === '\\' || $inner[$i + 1] === "'")) {
                $decoded .= $inner[$i + 1];
                $i++;
            } else {
                $decoded .= $inner[$i];
            }
        }

        return $decoded;
    }
}
