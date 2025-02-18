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
use Twig\Loader\ArrayLoader;

class PimcorePlaceholderTest extends TestCase
{
    private TwigDefaultDelegatingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var TwigDefaultDelegatingEngine $templatingEngine */
        $templatingEngine = Pimcore::getContainer()->get('pimcore.templating.engine.delegating');

        $this->engine = $templatingEngine;
    }

    public function testBasic(): void
    {
        $this->engine->getTwigEnvironment()->setLoader(new ArrayLoader([
            'twig' => <<<TWIG
                {% do pimcore_placeholder('foo').set("Some text for later") %}
                <h3>First copy:</h3>
                {{ pimcore_placeholder('foo') }}
                <br/>
                <hr/>
                <h3>Second copy:</h3>
                {{ pimcore_placeholder('foo') }}
                <br/>
                <hr/>
                <h3>Third copy:</h3>
                {{ pimcore_placeholder('foo') }}
                <br/>
            TWIG,
        ]));

        $result = $this->engine->render('twig');

        $this->assertStringContainsString(<<<TEXT
                <h3>First copy:</h3>
                Some text for later
                <br/>
                <hr/>
                <h3>Second copy:</h3>
                Some text for later
                <br/>
                <hr/>
                <h3>Third copy:</h3>
                Some text for later
                <br/>
            TEXT,
            $result,
        );
    }

    public function testAggregateContent(): void
    {
        $this->engine->getTwigEnvironment()->setLoader(new ArrayLoader([
            'twig' => <<<TWIG
            {% do pimcore_placeholder('foo').setPrefix("<ul>\n<li>")
                .setSeparator("</li>\n<li>")
                .setIndent(4)
                .setPostfix("</li>\n</ul>")
            %}
            {% for datum in data %}{% do pimcore_placeholder('foo').append(datum.title) %}{% endfor %}
            <h3>First copy:</h3>
            {{ pimcore_placeholder('foo') }}
            <br/>
            <hr/>
            <h3>Second copy:</h3>
            {{ pimcore_placeholder('foo') }}
            <br/>
            <hr/>
            <h3>Third copy:</h3>
            {{ pimcore_placeholder('foo') }}
            <br/>
            TWIG,
        ]));

        $result = $this->engine->render(
            'twig',
            [
                'data' => [
                    ['title' => 'oh'],
                    ['title' => 'my'],
                    ['title' => 'list'],
                ],
            ],
        );

        $this->assertStringContainsString(<<<TEXT
            <h3>First copy:</h3>
                <ul>
                <li>oh</li>
                <li>my</li>
                <li>list</li>
                </ul>
            <br/>
            <hr/>
            <h3>Second copy:</h3>
                <ul>
                <li>oh</li>
                <li>my</li>
                <li>list</li>
                </ul>
            <br/>
            <hr/>
            <h3>Third copy:</h3>
                <ul>
                <li>oh</li>
                <li>my</li>
                <li>list</li>
                </ul>
            <br/>
            TEXT,
            $result,
        );
    }

    public function testCaptureContent(): void
    {
        $this->engine->getTwigEnvironment()->setLoader(new ArrayLoader([
            'twig' => <<<TWIG
            {% do pimcore_placeholder('foo').captureStart() %}
            {% for datum in data %}
            <div class="foo">
                <h2>{{ datum.title }}</h2>
                <p>{{ datum.content }}</p>
            </div>
            {% endfor %}
            {% do pimcore_placeholder('foo').captureEnd() %}
            <h3>First copy:</h3>
            {{ pimcore_placeholder('foo') }}
            <br/>
            <hr/>
            <h3>Second copy:</h3>
            {{ pimcore_placeholder('foo') }}
            <br/>
            <hr/>
            <h3>Third copy:</h3>
            {{ pimcore_placeholder('foo') }}
            <br/>
            TWIG,
        ]));

        $result = $this->engine->render(
            'twig',
            [
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
            ],
        );

        $this->assertStringContainsString(<<<TEXT
            <h3>First copy:</h3>
            <div class="foo">
                <h2>Title 1</h2>
                <p>Content 1</p>
            </div>
            <div class="foo">
                <h2>Title 2</h2>
                <p>Content 2</p>
            </div>

            <br/>
            <hr/>
            <h3>Second copy:</h3>
            <div class="foo">
                <h2>Title 1</h2>
                <p>Content 1</p>
            </div>
            <div class="foo">
                <h2>Title 2</h2>
                <p>Content 2</p>
            </div>

            <br/>
            <hr/>
            <h3>Third copy:</h3>
            <div class="foo">
                <h2>Title 1</h2>
                <p>Content 1</p>
            </div>
            <div class="foo">
                <h2>Title 2</h2>
                <p>Content 2</p>
            </div>

            <br/>
            TEXT,
            $result,
        );
    }

    public function testIssue16973(): void
    {
        $this->engine->getTwigEnvironment()->setLoader(new ArrayLoader([
            'twig' => <<<TWIG
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Example</title>
            </head>

            <body>
            {# Default capture: append #}
            {% set data = [{"title": "title1", "content": "content1"}, {"title": "title2", "content": "content2"}] %}

            {% do pimcore_placeholder('foo').captureStart() %}

            {# If placeholder is working this section is not rendered directly but captured into placeholder#}

            {% for datum in data %}
                <div class="foo">
                    <h2>{{ datum.title }}</h2>
                    <p>{{ datum.content }}</p>
                </div>
            {% endfor %}

            {% do pimcore_placeholder('foo').captureEnd() %}

            {# If placeholder is working it should render three sections of same content #}

            <h3>First copy:</h3>
            {{ pimcore_placeholder('foo') }}
            <br/>
            <hr/>
            <h3>Second copy:</h3>
            {{ pimcore_placeholder('foo') }}
            <br/>
            <hr/>
            <h3>Third copy:</h3>
            {{ pimcore_placeholder('foo') }}
            <br/>
            <hr/>
            </body>
            </html>
            TWIG,
        ]));
        $result = $this->engine->render('twig');

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
