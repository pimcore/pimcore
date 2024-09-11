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

namespace Pimcore\Tests\Twig;

use Pimcore;
use Pimcore\Templating\TwigDefaultDelegatingEngine;
use Pimcore\Tests\Support\Test\TestCase;
use Twig\Loader\FilesystemLoader;

class PimcorePlaceholderTest extends TestCase
{
    private TwigDefaultDelegatingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var TwigDefaultDelegatingEngine $templatingEngine */
        $templatingEngine = Pimcore::getContainer()->get('pimcore.templating.engine.delegating');
        $templatingEngine->getTwigEnvironment()->setLoader(new FilesystemLoader([realpath(__DIR__ . '/../Support/Resources/twig')]));

        $this->engine = $templatingEngine;
    }

    public function testBasic(): void
    {
        $result = $this->engine->render('pimcore_placeholder/basic.html.twig');

        $this->assertStringContainsString(<<<TEXT
            Some text for later
            AND AGAIN
            Some text for later
            TEXT,
            $result,
        );
    }

    public function testAggregateContent(): void
    {
        $result = $this->engine->render('pimcore_placeholder/aggregate_content.html.twig', [
            'data' => [
                ['title' => 'oh'],
                ['title' => 'my'],
                ['title' => 'list'],
            ],
        ]);

        $this->assertStringContainsString(<<<TEXT
                <ul>
                <li>oh</li>
                <li>my</li>
                <li>list</li>
                </ul>
            AND AGAIN
                <ul>
                <li>oh</li>
                <li>my</li>
                <li>list</li>
                </ul>
            TEXT,
            $result,
        );
    }

    public function testCaptureContent(): void
    {
        $result = $this->engine->render('pimcore_placeholder/capture_content.html.twig', [
            'data' => [
                [
                    'title' => 'Title 1',
                    'content' => 'Content 1',
                ],
                [
                    'title' => 'Title 2',
                    'content' => 'Content 2',
                ],
            ],
        ]);

        $this->assertStringContainsString(<<<TEXT
            <div class="foo">
                <h2>Title 1</h2>
                <p>Content 1</p>
            </div>
            <div class="foo">
                <h2>Title 2</h2>
                <p>Content 2</p>
            </div>

            AND AGAIN
            <div class="foo">
                <h2>Title 1</h2>
                <p>Content 1</p>
            </div>
            <div class="foo">
                <h2>Title 2</h2>
                <p>Content 2</p>
            </div>
            TEXT,
            $result,
        );
    }

    public function testIssue16973(): void
    {
        $result = $this->engine->render('pimcore_placeholder/issue_16973.html.twig');

        $this->assertStringContainsString(<<<TEXT
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Example</title>
            </head>

            <body>



            <h3>First copy:</h3>


                <div class="foo">
                    <h2>title1</h2>
                    <p>content1</p>
                </div>
                <div class="foo">
                    <h2>title2</h2>
                    <p>content2</p>
                </div>


            <br/>
            <hr/>
            <h3>Second copy:</h3>


                <div class="foo">
                    <h2>title1</h2>
                    <p>content1</p>
                </div>
                <div class="foo">
                    <h2>title2</h2>
                    <p>content2</p>
                </div>


            <br/>
            <hr/>
            <h3>Third copy:</h3>


                <div class="foo">
                    <h2>title1</h2>
                    <p>content1</p>
                </div>
                <div class="foo">
                    <h2>title2</h2>
                    <p>content2</p>
                </div>


            <br/>
            <hr/>
            </body>
            </html>
            TEXT,
            $result,
        );
    }
}
