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

namespace Pimcore\Tests\Unit\Notification\Service;

use InvalidArgumentException;
use Pimcore\Model\Notification\Service\NotificationServiceFilterParser;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class NotificationServiceFilterParserTest extends TestCase
{
    private function buildRequest(array $filter): Request
    {
        return Request::create('/admin/notifications/find', 'POST', [
            'filter' => json_encode($filter),
        ]);
    }

    public function testWhitelistedStringPropertyIsAccepted(): void
    {
        $parser = new NotificationServiceFilterParser($this->buildRequest([
            ['type' => 'string', 'property' => 'title', 'operator' => 'like', 'value' => 'test'],
        ]));

        $result = $parser->parse();

        $this->assertArrayHasKey('title_like', $result);
        $this->assertSame('title LIKE :title_like', $result['title_like']['condition']);
    }

    public function testWhitelistedDateOperatorIsAccepted(): void
    {
        $parser = new NotificationServiceFilterParser($this->buildRequest([
            ['type' => 'date', 'property' => 'timestamp', 'operator' => 'gt', 'value' => '2026-01-01'],
        ]));

        $result = $parser->parse();

        $this->assertArrayHasKey('creationDate_gt', $result);
        $this->assertSame('creationDate > :creationDate_gt', $result['creationDate_gt']['condition']);
    }

    public function testUnknownStringPropertyIsRejected(): void
    {
        // Not a whitelisted property: previously fell through to being used
        // verbatim as a raw SQL column name with no escaping.
        $payload = '1 UNION SELECT id,username,password,email,5 FROM users-- -';

        $parser = new NotificationServiceFilterParser($this->buildRequest([
            ['type' => 'string', 'property' => $payload, 'operator' => 'like', 'value' => ''],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $parser->parse();
    }

    public function testUnknownDatePropertyIsRejected(): void
    {
        $payload = '(SELECT 1 FROM (SELECT SLEEP(5))t)-- -';

        $parser = new NotificationServiceFilterParser($this->buildRequest([
            ['type' => 'date', 'property' => $payload, 'operator' => 'gt', 'value' => '2026-01-01'],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $parser->parse();
    }
}
