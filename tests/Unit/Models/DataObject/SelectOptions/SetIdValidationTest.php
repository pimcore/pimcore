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

namespace Pimcore\Tests\Unit\Model\DataObject\SelectOptions;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\SelectOptions\Config;

/**
 * Ensures Config::setId() only accepts valid identifiers. The ID is emitted verbatim into the
 * generated PHP enum file (as the enum name) and used to build the on-disk class file path, so it
 * must be a valid identifier. Without an anchored check here, an ID containing a valid identifier
 * substring anywhere (e.g. alongside injected PHP) previously passed, achieving RCE on autoload
 * (GHSA-g2vm-g4vq-qhwj, residual of GHSA-9x44-4gxf-8c25).
 */
class SetIdValidationTest extends TestCase
{
    /**
     * @dataProvider validIdProvider
     */
    public function testValidIdsAreAccepted(string $id): void
    {
        $config = new Config();
        $config->setId($id);

        $this->assertSame($id, $config->getId());
    }

    public static function validIdProvider(): array
    {
        return [
            'simple' => ['MyOption'],
            'with digits' => ['Option123'],
            'single letter followed by digit' => ['A1'],
        ];
    }

    public function testEmptyIdIsAccepted(): void
    {
        // An empty id is set transiently by the framework and must not throw.
        $config = new Config();
        $config->setId('');

        $this->assertSame('', $config->getId());
    }

    /**
     * @dataProvider invalidIdProvider
     */
    public function testInvalidIdsAreRejected(string $id): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Config())->setId($id);
    }

    public static function invalidIdProvider(): array
    {
        return [
            'GHSA-g2vm-g4vq-qhwj codegen injection payload' => [
                'Xa{};echo "___PIMCORE_RCE_PROOF___";file_put_contents("pimcore_rce_proof.txt","RCE_CONFIRMED");__halt_compiler();',
            ],
            'contains semicolon and braces' => ['Xa{};function __construct(){}//'],
            'leading digit' => ['1Option'],
            'lowercase start' => ['myOption'],
            'contains space' => ['My Option'],
            'contains dash' => ['My-Option'],
            'single capital letter (too short)' => ['A'],
        ];
    }
}
