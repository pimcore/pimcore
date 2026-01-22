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

namespace Pimcore\Tests\Twig;

use Carbon\Carbon;
use Pimcore;
use Pimcore\Templating\TwigDefaultDelegatingEngine;
use Pimcore\Tests\Support\Test\TestCase;
use Twig\Loader\ArrayLoader;

class PimcoreDateTest extends TestCase
{
    private TwigDefaultDelegatingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var TwigDefaultDelegatingEngine $templatingEngine */
        trigger_deprecation('pimcore/pimcore', '12.3', 'Retrieving "pimcore.templating.engine.delegating" from the container is deprecated and will be removed in 13.0. Inject Twig\Environment directly instead.');
        $templatingEngine = Pimcore::getContainer()->get('pimcore.templating.engine.delegating');

        $this->engine = $templatingEngine;
    }

    public function testPimcoreDateOutputIsoFormat(): void
    {
        $backupCarbonLocale = Carbon::getLocale();
        Carbon::setLocale('de_DE.utf8');

        $this->engine->getTwigEnvironment()->setLoader(new ArrayLoader([
            'twig' => <<<TWIG
            {{ pimcore_date("myDate", {
                "format": "d.m.Y",
                "outputIsoFormat": "dddd, MMMM D, YYYY h:mm"
            }) }}
            TWIG,
        ]));
        $snippet = new Pimcore\Model\Document\Snippet();
        $date = (new Pimcore\Model\Document\Editable\Date())
            ->setName('myDate')
            ->setDataFromResource(1733954969)
        ;
        $snippet->setEditable($date);

        $result = $this->engine->render(
            'twig',
            [
                'document' => $snippet,
            ]
        );

        $this->assertEquals('Mittwoch, Dezember 11, 2024 10:09', $result);

        Carbon::setLocale($backupCarbonLocale);
    }
}
