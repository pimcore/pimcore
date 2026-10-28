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

namespace Pimcore\Tests\Model\Element;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Pimcore;
use Pimcore\Db;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Support\Test\ModelTestCase;
use RuntimeException;
use Throwable;

/**
 * createFolderByPath() racing a concurrent request that creates one of the missing folders, e.g. parallel uploads of
 * a folder in the admin
 *
 * @group model.element.createfolderbypath
 */
class CreateFolderByPathRaceTest extends ModelTestCase
{
    private const TYPES = [
        'asset' => [Asset\Service::class, Asset\Folder::class, AssetEvents::class, 'assets', 'filename'],
        'document' => [Document\Service::class, Document\Folder::class, DocumentEvents::class, 'documents', 'key'],
        'object' => [DataObject\Service::class, DataObject\Folder::class, DataObjectEvents::class, 'objects', 'key'],
    ];

    private array $registeredListeners = [];

    protected function tearDown(): void
    {
        foreach ($this->registeredListeners as [$eventName, $listener]) {
            Pimcore::getEventDispatcher()->removeListener($eventName, $listener);
        }
        $this->registeredListeners = [];

        parent::tearDown();
    }

    public function testAssetFolderCommittedBeforeSave(): void
    {
        $this->assertFolderCommittedBeforeSave('asset');
    }

    public function testDocumentFolderCommittedBeforeSave(): void
    {
        $this->assertFolderCommittedBeforeSave('document');
    }

    public function testObjectFolderCommittedBeforeSave(): void
    {
        $this->assertFolderCommittedBeforeSave('object');
    }

    public function testAssetFolderInsertedConcurrently(): void
    {
        $this->assertFolderInsertedConcurrently('asset');
    }

    public function testDocumentFolderInsertedConcurrently(): void
    {
        $this->assertFolderInsertedConcurrently('document');
    }

    public function testObjectFolderInsertedConcurrently(): void
    {
        $this->assertFolderInsertedConcurrently('object');
    }

    /**
     * Within an outer transaction the folder committed by the other request is not visible, so the exception is
     * rethrown, and the outer transaction can still be used: the failed save only rolled back to its savepoint
     */
    public function testFolderInsertedConcurrentlyWithinTransaction(): void
    {
        $root = '/race-' . uniqid();
        $parent = Asset\Service::createFolderByPath($root);
        $db = Db::get();

        $db->beginTransaction();

        try {
            $process = $this->startConcurrentInsert('assets', 'filename', $parent->getId(), $root . '/', 'a');

            try {
                $this->assertGreaterThan(0, (int) $process['id'], 'the concurrent insert failed: ' . $process['error']);
                Asset\Service::createFolderByPath($root . '/a/b');
                $this->fail('no exception thrown');
            } catch (UniqueConstraintViolationException) {
                // expected
            } finally {
                $this->finishProcess($process);
            }

            $this->assertTrue($db->isTransactionActive());
            $this->assertFalse($db->isRollbackOnly());
            $this->assertNull(Asset::getByPath($root . '/a'));
            $this->assertSame(1, (int) $db->fetchOne('SELECT 1'));
        } finally {
            $db->rollBack();
        }

        $this->assertInstanceOf(Asset\Folder::class, Asset::getByPath($root . '/a'));
    }

    /**
     * A concurrently created element which is not a folder is not taken over as one
     */
    public function testConcurrentNonFolderIsNotUsed(): void
    {
        $root = '/race-' . uniqid();
        $parentId = Asset\Service::createFolderByPath($root)->getId();

        $created = false;
        $this->addListener(AssetEvents::PRE_ADD, function (ElementEventInterface $event) use (&$created, $parentId): void {
            if (!$created && self::isNewChild($event->getElement(), $parentId, 'a')) {
                $created = true;
                $asset = new Asset\Text();
                $asset->setParentId($parentId);
                $asset->setFilename('a');
                $asset->setData('not a folder');
                $asset->save();
            }
        });

        $this->expectException(DuplicateFullPathException::class);
        Asset\Service::createFolderByPath($root . '/a/b');
    }

    /**
     * An exception which is not about a duplicate path is not swallowed, even if the path exists afterwards
     */
    public function testUnrelatedFailureIsRethrown(): void
    {
        $root = '/race-' . uniqid();
        $parentId = Asset\Service::createFolderByPath($root)->getId();

        $thrown = false;
        $this->addListener(AssetEvents::PRE_ADD, function (ElementEventInterface $event) use (&$thrown, $root, $parentId): void {
            if (!$thrown && self::isNewChild($event->getElement(), $parentId, 'a')) {
                $thrown = true;
                Asset\Service::createFolderByPath($root . '/a');

                throw new RuntimeException('unrelated failure');
            }
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unrelated failure');
        Asset\Service::createFolderByPath($root . '/a/b');
    }

    /**
     * The other request has committed the folder after the pathExists() check, before the save looks for duplicates
     */
    private function assertFolderCommittedBeforeSave(string $type): void
    {
        [$service, $folderClass, $events] = self::TYPES[$type];
        $root = '/race-' . uniqid();
        $parentId = $service::createFolderByPath($root)->getId();

        $concurrentFolder = null;
        $this->addListener($events::PRE_ADD, function (ElementEventInterface $event) use (&$concurrentFolder, $service, $root, $parentId): void {
            if ($concurrentFolder === null && self::isNewChild($event->getElement(), $parentId, 'a')) {
                $concurrentFolder = false; // the save of the other request dispatches this event too
                $concurrentFolder = $service::createFolderByPath($root . '/a');
            }
        });
        $failure = $this->captureAddFailure($events);

        $folder = $service::createFolderByPath($root . '/a/b');

        $this->assertInstanceOf(DuplicateFullPathException::class, $failure(), 'the race was not hit');
        $this->assertInstanceOf($folderClass, $concurrentFolder);
        $this->assertInstanceOf($folderClass, $folder);
        $this->assertSame($root . '/a/b', $folder->getRealFullPath());
        $this->assertSame($concurrentFolder->getId(), $folder->getParentId());
    }

    /**
     * The other request has inserted the folder, but not committed yet, so the pathExists() check and the save both
     * miss it and the insert waits for the other transaction, then violates the unique full path
     */
    private function assertFolderInsertedConcurrently(string $type): void
    {
        [$service, $folderClass, $events, $table, $keyColumn] = self::TYPES[$type];
        $root = '/race-' . uniqid();
        $parent = $service::createFolderByPath($root);
        $failure = $this->captureAddFailure($events);

        $process = $this->startConcurrentInsert($table, $keyColumn, $parent->getId(), $root . '/', 'a');

        try {
            $concurrentFolderId = (int) $process['id'];
            $this->assertGreaterThan(0, $concurrentFolderId, 'the concurrent insert failed: ' . $process['error']);

            $folder = $service::createFolderByPath($root . '/a/b');
        } finally {
            $this->finishProcess($process);
        }

        $exception = $failure();
        $this->assertNotNull($exception, 'the race was not hit');
        $this->assertTrue(self::isCausedBy($exception, UniqueConstraintViolationException::class), (string) $exception);
        $this->assertInstanceOf($folderClass, $folder);
        $this->assertSame($root . '/a/b', $folder->getRealFullPath());
        $this->assertSame($concurrentFolderId, $folder->getParentId());
        $this->assertInstanceOf($folderClass, $service::getElementByPath($type, $root . '/a'));
    }

    private function addListener(string $eventName, callable $listener): void
    {
        Pimcore::getEventDispatcher()->addListener($eventName, $listener);
        $this->registeredListeners[] = [$eventName, $listener];
    }

    /**
     * @return callable(): ?Throwable the exception of the first failed add
     */
    private function captureAddFailure(string $events): callable
    {
        $exception = null;
        $this->addListener($events::POST_ADD_FAILURE, function (ElementEventInterface $event) use (&$exception): void {
            $exception ??= $event->getArgument('exception');
        });

        return function () use (&$exception): ?Throwable {
            return $exception;
        };
    }

    private static function isNewChild(ElementInterface $element, int $parentId, string $key): bool
    {
        return !$element->getId() && $element->getParentId() === $parentId && $element->getKey() === $key;
    }

    private static function isCausedBy(Throwable $exception, string $class): bool
    {
        for ($e = $exception; $e !== null; $e = $e->getPrevious()) {
            if ($e instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * Inserts a folder row in a separate process and transaction, which is committed after a second
     *
     * @return array{handle: resource, pipes: array<int, resource>, id: string, error: string}
     */
    private function startConcurrentInsert(string $table, string $keyColumn, int $parentId, string $path, string $key): array
    {
        $params = Db::get()->getParams();
        $dsn = isset($params['unix_socket'])
            ? sprintf('mysql:unix_socket=%s;dbname=%s', $params['unix_socket'], $params['dbname'])
            : sprintf('mysql:host=%s;port=%d;dbname=%s', $params['host'] ?? 'localhost', $params['port'] ?? 3306, $params['dbname']);

        $script = <<<'PHP'
            $p = json_decode(getenv('PIMCORE_RACE_INSERT'), true);
            $pdo = new PDO($p['dsn'], $p['user'], $p['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('SET NAMES utf8mb4');
            $pdo->beginTransaction();
            $pdo->prepare(sprintf(
                'INSERT INTO `%s` (`parentId`, `type`, `%s`, `path`, `creationDate`, `modificationDate`, `userOwner`, `userModification`) VALUES (?, ?, ?, ?, ?, ?, 1, 0)',
                $p['table'],
                $p['keyColumn']
            ))->execute([$p['parentId'], 'folder', $p['key'], $p['path'], time(), time()]);
            echo $pdo->lastInsertId(), "\n";
            fflush(STDOUT);
            sleep(1);
            $pdo->commit();
            PHP;

        $env = getenv();
        $env['PIMCORE_RACE_INSERT'] = json_encode([
            'dsn' => $dsn,
            'user' => $params['user'] ?? '',
            'password' => $params['password'] ?? '',
            'table' => $table,
            'keyColumn' => $keyColumn,
            'parentId' => $parentId,
            'path' => $path,
            'key' => $key,
        ]);

        $handle = proc_open([PHP_BINARY, '-r', $script], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
        if (!is_resource($handle)) {
            $this->fail('could not start the concurrent insert');
        }

        // wait until the row is inserted (and locked), the transaction is committed a second later
        $id = trim((string) fgets($pipes[1]));
        $error = $id === '' ? (string) stream_get_contents($pipes[2]) : '';

        return ['handle' => $handle, 'pipes' => $pipes, 'id' => $id, 'error' => $error];
    }

    private function finishProcess(array $process): void
    {
        foreach ($process['pipes'] as $pipe) {
            fclose($pipe);
        }
        proc_close($process['handle']);
    }
}
