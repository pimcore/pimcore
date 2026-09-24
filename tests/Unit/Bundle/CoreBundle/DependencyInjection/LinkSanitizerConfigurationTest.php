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

namespace Pimcore\Tests\Unit\Bundle\CoreBundle\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Configuration;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Config\Definition\Processor;

class LinkSanitizerConfigurationTest extends TestCase
{
    private function process(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    public function testLinkSanitizerStrictDefaultsToFalse(): void
    {
        $config = $this->process([[]]);

        $this->assertFalse($config['documents']['editables']['link_sanitizer']['strict']);
    }

    public function testLinkSanitizerStrictAcceptsTrue(): void
    {
        $config = $this->process([[
            'documents' => [
                'editables' => [
                    'link_sanitizer' => [
                        'strict' => true,
                    ],
                ],
            ],
        ]]);

        $this->assertTrue($config['documents']['editables']['link_sanitizer']['strict']);
    }

    /**
     * @dataProvider quotedBooleanStringProvider
     */
    public function testLinkSanitizerStrictParsesQuotedBooleanStrings(string $value, bool $expected): void
    {
        $config = $this->process([[
            'documents' => [
                'editables' => [
                    'link_sanitizer' => [
                        'strict' => $value,
                    ],
                ],
            ],
        ]]);

        $this->assertSame($expected, $config['documents']['editables']['link_sanitizer']['strict']);
    }

    public static function quotedBooleanStringProvider(): array
    {
        return [
            'quoted false' => ['false', false],
            'quoted no' => ['no', false],
            'quoted off' => ['off', false],
            'quoted 0' => ['0', false],
            'quoted true' => ['true', true],
            'quoted yes' => ['yes', true],
            'quoted on' => ['on', true],
            'quoted 1' => ['1', true],
        ];
    }

    public function testBlockedUrlSchemesDefaultsToJavascriptAndVbscript(): void
    {
        $config = $this->process([[]]);

        $this->assertSame(
            ['javascript:', 'vbscript:'],
            $config['documents']['editables']['link_sanitizer']['blocked_url_schemes']
        );
    }

    public function testBlockedUrlSchemesAcceptsACustomList(): void
    {
        $config = $this->process([[
            'documents' => [
                'editables' => [
                    'link_sanitizer' => [
                        'blocked_url_schemes' => ['javascript:', 'mailto:', 'tel:'],
                    ],
                ],
            ],
        ]]);

        $this->assertSame(
            ['javascript:', 'mailto:', 'tel:'],
            $config['documents']['editables']['link_sanitizer']['blocked_url_schemes']
        );
    }

    public function testBlockUnsafeDataUrlsDefaultsToTrue(): void
    {
        $config = $this->process([[]]);

        $this->assertTrue($config['documents']['editables']['link_sanitizer']['block_unsafe_data_urls']);
    }

    public function testBlockUnsafeDataUrlsAcceptsFalse(): void
    {
        $config = $this->process([[
            'documents' => [
                'editables' => [
                    'link_sanitizer' => [
                        'block_unsafe_data_urls' => false,
                    ],
                ],
            ],
        ]]);

        $this->assertFalse($config['documents']['editables']['link_sanitizer']['block_unsafe_data_urls']);
    }
}
