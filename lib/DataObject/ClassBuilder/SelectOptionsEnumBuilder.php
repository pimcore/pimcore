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

namespace Pimcore\DataObject\ClassBuilder;

use Exception;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\DataObject\SelectOptions\Data\SelectOption;
use Pimcore\Model\DataObject\SelectOptions\Traits\EnumGetValuesTrait;
use Pimcore\Model\DataObject\SelectOptions\Traits\EnumTryFromNullableTrait;
use Pimcore\Model\DataObject\SelectOptionsInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class SelectOptionsEnumBuilder implements SelectOptionsEnumBuilderInterface
{
    public function buildEnum(Config $config): string
    {
        $template = $this->getTemplate();

        return strtr(
            $template,
            [
                '%namespace%' => $config->getNamespace(),
                '%enumName%' => $config->getEnumName(),
                '%interfaces%' => $this->generateInterfaces($config),
                '%traits%' => $this->generateTraits($config),
                '%cases%' => $this->generateCases($config),
                '%labelMatches%' => $this->generateLabelMatches($config),
            ]
        );
    }

    protected function generateInterfaces(Config $config): string
    {
        $interfaces = $this->parseClasses($config->getImplementsInterfaces());

        $baseInterface = '\\' . SelectOptionsInterface::class;
        $extendsBaseInterface = false;
        $implements = [];
        foreach ($interfaces as $interface) {
            if (!interface_exists($interface)) {
                throw new Exception('Interface ' . $interface . ' does not exist', 1676878234790);
            }

            if (is_subclass_of($interface, $baseInterface)) {
                $extendsBaseInterface = true;
            }

            $implements[] = $interface;
        }

        // Add base interface if none of the custom interfaces extend it
        if (!$extendsBaseInterface) {
            array_unshift($implements, $baseInterface);
        }

        return implode(', ', $implements);
    }

    protected function generateTraits(Config $config): string
    {
        $template = 'use %trait%;';

        $traits = $this->parseClasses($config->getUseTraits());
        // Prepend default traits
        array_unshift(
            $traits,
            '\\' . EnumGetValuesTrait::class,
            '\\' . EnumTryFromNullableTrait::class,
        );

        $uses = [];
        foreach ($traits as $trait) {
            if (!trait_exists($trait)) {
                throw new Exception('Trait ' . $trait . ' does not exist', 1676878234791);
            }
            $uses[] = strtr($template, ['%trait%' => $trait]);
        }

        return $this->implodeTemplateValues($uses, addEndLineBreak: true);
    }

    /**
     * @return string[]
     */
    protected function parseClasses(string $classes): array
    {
        // Prefix backslash
        return array_map(
            fn (string $class) => '\\' . ltrim($class),
            explode_and_trim(',', $classes)
        );
    }

    protected function generateCases(Config $config): string
    {
        $template = 'case %caseName% = \'%optionValue%\';';

        $cases = $caseNames = [];
        foreach ($config->getSelectOptions() as $selectOption) {
            $caseName = $this->generateCaseName($selectOption);
            if (isset($caseNames[$caseName])) {
                throw new Exception(
                    sprintf(
                        'Case \'%s\' for value \'%s\' already exists for value \'%s\'. Configure a name or ensure the alphanumeric characters are unique.',
                        $caseName,
                        $selectOption->getValue(),
                        $caseNames[$caseName]
                    ),
                    1676890789419
                );
            }

            // Store value for unique check
            $value = $selectOption->getValue();
            $caseNames[$caseName] = $value;

            $cases[] = strtr(
                $template,
                [
                    '%caseName%' => $caseName,
                    '%optionValue%' => $this->escapeSingleQuote($value),
                ]
            );
        }

        return $this->implodeTemplateValues($cases);
    }

    protected function generateLabelMatches(Config $config): string
    {
        if (!$config->hasSelectOptions()) {
            return 'default => \'\'';
        }

        $template = 'self::%caseName% => \'%optionLabel%\',';

        $labelMatches = [];
        foreach ($config->getSelectOptions() as $selectOption) {
            $label = $selectOption->hasLabel() ? $selectOption->getLabel() : $selectOption->getValue();
            $labelMatches[] = strtr(
                $template,
                [
                    '%caseName%' => $this->generateCaseName($selectOption),
                    '%optionLabel%' => $this->escapeSingleQuote($label),
                ]
            );
        }

        return $this->implodeTemplateValues($labelMatches, 12);
    }

    protected function implodeTemplateValues(array $lines, int $indent = 4, bool $addEndLineBreak = false): string
    {
        $content = implode("\n" . $this->indent($indent), $lines);
        if ($addEndLineBreak && (bool)count($lines)) {
            $content .= "\n";
        }

        return $content;
    }

    protected function indent(int $spaces = 4): string
    {
        return str_repeat(' ', $spaces);
    }

    protected function escapeSingleQuote(string $value): string
    {
        return str_replace("'", "\\'", $value);
    }

    protected function generateCaseName(SelectOption $selectOption): string
    {
        $selectOptionName = $this->getSelectOptionName($selectOption);

        // Start with a letter or underscore, followed by zero or more alphanumeric and underscore characters
        if (!preg_match('/^[A-Z-a-z_][A-Za-z0-9_]*$/', $selectOptionName)) {
            throw new Exception(
                sprintf(
                    'Invalid name \'%s\' for option with value \'%s\'. Must be alphanumeric and start with a letter (underscores allowed). Configure a name or use a different value.',
                    $selectOptionName,
                    $selectOption->getValue()
                ),
                1676896232762
            );
        }

        // Use prefix to assure the case doesn't start with a numeric value
        return $selectOptionName;
    }

    protected function getSelectOptionName(SelectOption $selectOption): string
    {
        if ($selectOption->hasName()) {
            return $selectOption->getName();
        }

        // Attempt to convert value to case name
        $value = $selectOption->getValue();
        // Apply slug to remove invalid characters
        $caseName = (new AsciiSlugger())
            ->slug($value, '_', 'en')
            ->toString();
        $caseName = $this->toUpperCamelCase($caseName);

        if (empty($caseName) && $caseName !== '0') {
            throw new Exception(
                'Unable to convert value \'' . $value . '\' to case name. Configure a name or use a different value.',
                1676895007458
            );
        }

        return $caseName;
    }

    protected function toUpperCamelCase(string $value): string
    {
        return str_replace(
            ' ',
            '_',
            ucwords(
                str_replace('_', ' ', $value)
            )
        );
    }

    protected function getTemplate(): string
    {
        return '<?php

namespace %namespace%;

enum %enumName%: string implements %interfaces%
{
    %traits%
    %cases%

    public function getLabel(): string
    {
        return match ($this) {
            %labelMatches%
        };
    }
}
';
    }
}
