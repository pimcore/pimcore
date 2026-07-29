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

namespace Pimcore\Tests\Unit\CoreBundle\Command;

use Pimcore\Bundle\CoreBundle\Command\RequirementsCheckCommand;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Requirements\Check;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class RequirementsCheckCommandTest extends TestCase
{
    private const DESCRIBED_CHECK = 'pdftotext - (part of poppler-utils)';

    private const DESCRIPTION = 'Extracts plain text from PDFs.';

    private const UNDESCRIBED_CHECK = 'Graphviz';

    /**
     * The real command reads the database, which is out of scope here - only the rendering of
     * the checks is under test, so the check set is replaced with a fixture.
     */
    private function makeCommand(): RequirementsCheckCommand
    {
        return new class() extends RequirementsCheckCommand {
            public function __construct()
            {
                parent::__construct('pimcore:system:requirements:check');
            }

            protected function loadChecks(): array
            {
                return [
                    'checksPHP' => [],
                    'checksMySQL' => [],
                    'checksFS' => [],
                    'checksApps' => [
                        new Check([
                            'name' => RequirementsCheckCommandTest::DESCRIBED_CHECK,
                            'state' => Check::STATE_WARNING,
                            'message' => RequirementsCheckCommandTest::DESCRIPTION,
                        ]),
                        new Check([
                            'name' => RequirementsCheckCommandTest::UNDESCRIBED_CHECK,
                            'state' => Check::STATE_WARNING,
                        ]),
                    ],
                ];
            }
        };
    }

    private function runCommand(int $verbosity = OutputInterface::VERBOSITY_NORMAL): string
    {
        $tester = new CommandTester($this->makeCommand());
        $tester->execute([], ['interactive' => false, 'verbosity' => $verbosity]);

        return $tester->getDisplay();
    }

    public function testNormalOutputListsChecksWithoutDescriptions(): void
    {
        $display = $this->runCommand();

        $this->assertStringContainsString(self::DESCRIBED_CHECK, $display);
        $this->assertStringContainsString(self::UNDESCRIBED_CHECK, $display);
        $this->assertStringNotContainsString(self::DESCRIPTION, $display);
    }

    public function testVerboseOutputAddsTheDescriptionColumn(): void
    {
        $display = $this->runCommand(OutputInterface::VERBOSITY_VERBOSE);

        $this->assertStringContainsString(self::DESCRIBED_CHECK, $display);
        $this->assertStringContainsString(self::DESCRIPTION, $display);
    }

    /**
     * Check::getMessage() falls back to "<name> is required." when no message is set, which is
     * wrong for the checks reported as a warning. The description column must stay empty instead.
     */
    public function testVerboseOutputDoesNotInventADescriptionForChecksWithoutOne(): void
    {
        $display = $this->runCommand(OutputInterface::VERBOSITY_VERBOSE);

        $this->assertStringContainsString(self::UNDESCRIBED_CHECK, $display);
        $this->assertStringNotContainsString('is required', $display);
    }
}
