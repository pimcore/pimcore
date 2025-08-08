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
        $templatingEngine = Pimcore::getContainer()->get('pimcore.templating.engine.delegating');

        $this->engine = $templatingEngine;
    }

    public function testPimcoreDateOutputFormat(): void
    {
        $backupLocale = setlocale(LC_TIME, '0');
        setlocale(LC_TIME, 'en_US.UTF-8');

        $this->engine->getTwigEnvironment()->setLoader(new ArrayLoader([
            'twig' => <<<TWIG
            {{ pimcore_date("myDate", {
                "format": "d.m.Y",
                "outputFormat": "%A, %B %e, %Y %I:%M"
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

        $this->assertEquals('Wednesday, December 11, 2024 10:09', $result);

        setlocale(LC_TIME, $backupLocale);
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
