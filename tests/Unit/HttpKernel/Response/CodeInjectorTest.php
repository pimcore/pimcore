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

namespace Pimcore\Tests\Unit\HttpKernel\Response;

use InvalidArgumentException;
use PHPUnit_Framework_MockObject_MockObject;
use Pimcore\Http\Response\CodeInjector;
use Pimcore\Http\ResponseHelper;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

class CodeInjectorTest extends TestCase
{
    private ResponseHelper|PHPUnit_Framework_MockObject_MockObject $responseHelper;

    private CodeInjector $injector;

    private string $codePart = '<!-- INJECTED -->';

    protected function setUp(): void
    {
        parent::setUp();

        $this->responseHelper = $this->getMockBuilder(ResponseHelper::class)->getMock();
        $this->injector = new CodeInjector($this->responseHelper);
    }

    public function testNotChangedIfNoMatch(): void
    {
        $html = '<html><body></body></html>';
        $result = $this->injector->injectIntoHtml($html, $this->codePart, CodeInjector::SELECTOR_HEAD, CodeInjector::POSITION_BEGINNING);

        $this->assertEquals($html, $result);
    }

    public function testResponseNotChangedIfResponseHelperDoesNotClassifyAsHtml(): void
    {
        $this->responseHelper
            ->method('isHtmlResponse')
            ->willReturn(false);

        $content = '<html><head></head><body>foo</body></html>';
        $response = new Response($content);

        $this->injector->inject($response, $this->codePart, CodeInjector::SELECTOR_BODY, CodeInjector::POSITION_BEGINNING);

        // nothing changed if ResponseHelper does not classify response as HTML
        $this->assertEquals($content, $response->getContent());
    }

    /**
     * @dataProvider injectPresetProvider
     */
    public function testInjectPreset(string $selector, string $position, string $source, string $expected): void
    {
        $this->testInject($selector, $position, $source, $expected);
    }

    /**
     * @group dom
     *
     * @dataProvider injectDOMProvider
     */
    public function testInjectDOM(string $selector, string $position, string $source, string $expected): void
    {
        $this->testInject($selector, $position, $source, $expected);
    }

    private function testInject(string $selector, string $position, string $source, string $expected): void
    {
        $result = $this->injector->injectIntoHtml($source, $this->codePart, $selector, $position);

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider invalidTypeProvider
     */
    public function testInvalidPosition(string $position): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->injector->injectIntoHtml('foo', 'bar', CodeInjector::SELECTOR_BODY, $position);
    }

    public function presetSelectorProvider(): array
    {
        $reflector = new ReflectionClass(CodeInjector::class);

        $property = $reflector->getProperty('presetSelectors');

        $data = [];
        foreach ($property->getValue() as $value) {
            $data[] = [$value];
        }

        return $data;
    }

    public function validPositionProvider(): array
    {
        $reflector = new ReflectionClass(CodeInjector::class);

        $property = $reflector->getProperty('validPositions');

        $data = [];
        foreach ($property->getValue() as $value) {
            $data[] = [$value];
        }

        return $data;
    }

    public function invalidTypeProvider(): array
    {
        return [['foo'], ['bar']];
    }

    public function injectPresetProvider(): array
    {
        $data = [];

        $source = <<<EOF
<html>
<head>
    <!-- ORIG HEAD -->
</head>
<body class="foo" bar>
    <!-- ORIG BODY -->
</body>
</html>
EOF;

        $data[] = [
            CodeInjector::SELECTOR_HEAD,
            CodeInjector::POSITION_BEGINNING,
            $source,
            <<<EOF
<html>
<head><!-- INJECTED -->
    <!-- ORIG HEAD -->
</head>
<body class="foo" bar>
    <!-- ORIG BODY -->
</body>
</html>
EOF
        ];

        $data[] = [
            CodeInjector::SELECTOR_HEAD,
            CodeInjector::POSITION_END,
            $source,
            <<<EOF
<html>
<head>
    <!-- ORIG HEAD -->
<!-- INJECTED --></head>
<body class="foo" bar>
    <!-- ORIG BODY -->
</body>
</html>
EOF
        ];

        $data[] = [
            CodeInjector::SELECTOR_HEAD,
            CodeInjector::REPLACE,
            $source,
            <<<EOF
<html>
<head><!-- INJECTED --></head>
<body class="foo" bar>
    <!-- ORIG BODY -->
</body>
</html>
EOF
        ];

        $data[] = [
            CodeInjector::SELECTOR_BODY,
            CodeInjector::POSITION_BEGINNING,
            $source,
            <<<EOF
<html>
<head>
    <!-- ORIG HEAD -->
</head>
<body class="foo" bar><!-- INJECTED -->
    <!-- ORIG BODY -->
</body>
</html>
EOF
        ];

        $data[] = [
            CodeInjector::SELECTOR_BODY,
            CodeInjector::POSITION_END,
            $source,
            <<<EOF
<html>
<head>
    <!-- ORIG HEAD -->
</head>
<body class="foo" bar>
    <!-- ORIG BODY -->
<!-- INJECTED --></body>
</html>
EOF
        ];

        $data[] = [
            CodeInjector::SELECTOR_BODY,
            CodeInjector::REPLACE,
            $source,
            <<<EOF
<html>
<head>
    <!-- ORIG HEAD -->
</head>
<body class="foo" bar><!-- INJECTED --></body>
</html>
EOF
        ];

        return $data;
    }

    public function injectDOMProvider(): array
    {
        $data = [];

        $domSource = <<<EOF
<html>
<head>
    <!-- ORIG HEAD -->
</head>
<body class="foo" bar>
    <!-- ORIG BODY -->
    <div class="bar"><!-- ORIG DIV --></div>
</body>
</html>
EOF;

        $data[] = [
            'body > div.bar',
            CodeInjector::REPLACE,
            $domSource,
            <<<EOF
<html>
<head>
    <!-- ORIG HEAD -->
</head>
<body class="foo" bar>
    <!-- ORIG BODY -->
    <div class="bar"><!-- INJECTED --></div>
</body>
</html>
EOF
        ];

        $data[] = [
            'body > div.bar',
            CodeInjector::POSITION_BEGINNING,
            $domSource,
            <<<EOF
<html>
<head>
    <!-- ORIG HEAD -->
</head>
<body class="foo" bar>
    <!-- ORIG BODY -->
    <div class="bar"><!-- INJECTED --><!-- ORIG DIV --></div>
</body>
</html>
EOF
        ];

        $data[] = [
            'body > div.bar',
            CodeInjector::POSITION_END,
            $domSource,
            <<<EOF
<html>
<head>
    <!-- ORIG HEAD -->
</head>
<body class="foo" bar>
    <!-- ORIG BODY -->
    <div class="bar"><!-- ORIG DIV --><!-- INJECTED --></div>
</body>
</html>
EOF
        ];

        $data[] = [
            '.non-existing',
            CodeInjector::POSITION_END,
            $domSource,
            $domSource,
        ];

        return $data;
    }
}
