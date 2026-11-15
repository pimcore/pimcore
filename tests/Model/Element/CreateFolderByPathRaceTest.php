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
use Pimcore\Tests\Support\Util\TestHelper;
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
     * Within an outer transaction the folder committed by the other request is not visible (under the default
     * REPEATABLE READ, set explicitly here), so the exception is rethrown, and the outer transaction can still be
     * used: the failed save only rolled back to its savepoint
     *
     * @dataProvider types
     */
    public function testFolderInsertedConcurrentlyWithinTransaction(string $type): void
    {
        [$service, $folderClass, , $table, $keyColumn] = self::TYPES[$type];
        $root = '/race-' . uniqid();
        $parent = $service::createFolderByPath($root);
        $db = Db::get();

        $db->executeStatement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $db->beginTransaction();

        try {
            $process = $this->startConcurrentInsert($table, $keyColumn, $parent->getId(), $root . '/', 'a');

            try {
                $this->assertGreaterThan(0, (int) $process['id'], 'the concurrent insert failed: ' . $process['error']);
                $exception = $this->captureException(fn () => $service::createFolderByPath($root . '/a/b'));
            } finally {
                $this->finishProcess($process);
            }

            $this->assertTrue(self::isCausedBy($exception, UniqueConstraintViolationException::class), (string) $exception);
            $this->assertTrue($db->isTransactionActive());
            $this->assertFalse($db->isRollbackOnly());
            $this->assertNull($service::getElementByPath($type, $root . '/a'));
            $this->assertSame(1, (int) $db->fetchOne('SELECT 1'));
        } finally {
            // only if still active, so that a failed assertion above is not replaced by the exception of rollBack()
            if ($db->isTransactionActive()) {
                $db->rollBack();
            }
        }

        $this->assertInstanceOf($folderClass, $service::getElementByPath($type, $root . '/a'));
    }

    /**
     * A concurrently created element which is not a folder is not taken over as one
     *
     * @dataProvider types
     */
    public function testConcurrentNonFolderIsNotUsed(string $type): void
    {
        [$service, , $events] = self::TYPES[$type];
        $root = '/race-' . uniqid();
        $parentId = $service::createFolderByPath($root)->getId();

        $created = false;
        $this->addListener($events::PRE_ADD, function (ElementEventInterface $event) use (&$created, $type, $parentId): void {
            if (!$created && self::isNewChild($event->getElement(), $parentId, 'a')) {
                $created = true;
                self::createNonFolder($type, $parentId, 'a');
            }
        });

        $exception = $this->captureException(fn () => $service::createFolderByPath($root . '/a/b'));

        $this->assertTrue(self::isCausedBy($exception, DuplicateFullPathException::class), (string) $exception);
        $this->assertNull($service::getElementByPath($type, $root . '/a/b'));
    }

    /**
     * The other request creates the requested folder's parent and, at the requested path, an element which is not a
     * folder: that is not returned as the folder
     *
     * @dataProvider types
     */
    public function testConcurrentNonFolderAtRequestedPathIsNotReturned(string $type): void
    {
        [$service, $folderClass, $events] = self::TYPES[$type];
        $root = '/race-' . uniqid();
        $parentId = $service::createFolderByPath($root)->getId();

        $nonFolder = null;
        $this->addListener($events::PRE_ADD, function (ElementEventInterface $event) use (&$nonFolder, $service, $type, $root, $parentId): void {
            if ($nonFolder === null && self::isNewChild($event->getElement(), $parentId, 'a')) {
                $nonFolder = false; // the save of the other request dispatches this event too
                $nonFolder = self::createNonFolder($type, $service::createFolderByPath($root . '/a')->getId(), 'b');
            }
        });

        $exception = $this->captureException(fn () => $service::createFolderByPath($root . '/a/b'));

        $this->assertInstanceOf(DuplicateFullPathException::class, $exception, (string) $exception);
        $this->assertSame($nonFolder->getId(), $exception->getDuplicateElement()?->getId());
        $this->assertInstanceOf($folderClass, $service::getElementByPath($type, $root . '/a'));
    }

    /**
     * An exception which is not about a duplicate path is not swallowed, even if the path exists afterwards
     *
     * @dataProvider types
     */
    public function testUnrelatedFailureIsRethrown(string $type): void
    {
        [$service, , $events] = self::TYPES[$type];
        $root = '/race-' . uniqid();
        $parentId = $service::createFolderByPath($root)->getId();

        $thrown = false;
        $this->addListener($events::PRE_ADD, function (ElementEventInterface $event) use (&$thrown, $service, $root, $parentId): void {
            if (!$thrown && self::isNewChild($event->getElement(), $parentId, 'a')) {
                $thrown = true;
                $service::createFolderByPath($root . '/a');

                throw new RuntimeException('unrelated failure');
            }
        });

        $exception = $this->captureException(fn () => $service::createFolderByPath($root . '/a/b'));

        $this->assertInstanceOf(RuntimeException::class, $exception, (string) $exception);
        $this->assertSame('unrelated failure', $exception->getMessage());
    }

    /**
     * The other request creates the whole remaining path, so the requested folder exists already when it is reached
     * after taking over its parent, and is the one returned
     *
     * @dataProvider types
     */
    public function testConcurrentRequestCreatesRemainingPath(string $type): void
    {
        [$service, $folderClass, $events] = self::TYPES[$type];
        $root = '/race-' . uniqid();
        $parentId = $service::createFolderByPath($root)->getId();

        $concurrentFolder = null;
        $this->addListener($events::PRE_ADD, function (ElementEventInterface $event) use (&$concurrentFolder, $service, $root, $parentId): void {
            if ($concurrentFolder === null && self::isNewChild($event->getElement(), $parentId, 'a')) {
                $concurrentFolder = false; // the save of the other request dispatches this event too
                $concurrentFolder = $service::createFolderByPath($root . '/a/b');
            }
        });
        $failure = $this->captureAddFailure($events);

        $folder = $service::createFolderByPath($root . '/a/b');

        $this->assertTrue(self::isCausedBy($failure(), DuplicateFullPathException::class), 'the race was not hit');
        $this->assertInstanceOf($folderClass, $concurrentFolder);
        $this->assertInstanceOf($folderClass, $folder);
        $this->assertSame($root . '/a/b', $folder->getRealFullPath());
        $this->assertSame($concurrentFolder->getId(), $folder->getId());
    }

    /**
     * A duplicate exception thrown by a post add listener, after the folder was committed, is not mistaken for a
     * concurrently created folder
     *
     * @dataProvider types
     */
    public function testPostAddDuplicateFailureIsRethrown(string $type): void
    {
        [$service, $folderClass, $events] = self::TYPES[$type];
        $root = '/race-' . uniqid();
        $parentId = $service::createFolderByPath($root)->getId();

        $this->addListener($events::POST_ADD, function (ElementEventInterface $event) use ($parentId): void {
            $element = $event->getElement();
            if ($element->getParentId() === $parentId && $element->getKey() === 'a') {
                throw new DuplicateFullPathException('duplicate from a listener');
            }
        });

        $exception = $this->captureException(fn () => $service::createFolderByPath($root . '/a/b'));

        $this->assertInstanceOf(DuplicateFullPathException::class, $exception, (string) $exception);
        $this->assertSame('duplicate from a listener', $exception->getMessage());
        $this->assertInstanceOf($folderClass, $service::getElementByPath($type, $root . '/a'));
        $this->assertNull($service::getElementByPath($type, $root . '/a/b'));
    }

    public static function types(): array
    {
        return ['asset' => ['asset'], 'document' => ['document'], 'object' => ['object']];
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

    private function captureException(callable $callable): Throwable
    {
        try {
            $callable();
        } catch (Throwable $e) {
            return $e;
        }

        $this->fail('no exception thrown');
    }

    private static function createNonFolder(string $type, int $parentId, string $key): ElementInterface
    {
        if ($type === 'asset') {
            $element = new Asset\Text();
            $element->setData('not a folder');
        } elseif ($type === 'document') {
            $element = new Document\Page();
        } else {
            $element = TestHelper::createEmptyObject('', false);
        }
        $element->setParentId($parentId);
        $element->setKey($key);
        $element->save();

        return $element;
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
     * Inserts a folder row in a separate process and transaction, which is committed once the test's connection
     * inserts into the table, i.e. the insert of the conflicting folder waits for the uncommitted row
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
            // commit only once the test's connection runs the conflicting insert, at most after the timeout: it waits
            // for the lock on this row then. SHOW PROCESSLIST lists the threads of the same user without the PROCESS
            // privilege
            $deadline = microtime(true) + $p['timeout'];
            do {
                usleep(10000);
                $inserting = false;
                foreach ($pdo->query('SHOW FULL PROCESSLIST', PDO::FETCH_ASSOC) as $thread) {
                    $inserting = $inserting || ($thread['Id'] == $p['connectionId']
                        && preg_match('/^\s*INSERT INTO\s+`?' . $p['table'] . '`?\s*\(/i', (string) $thread['Info']));
                }
            } while (!$inserting && microtime(true) < $deadline);
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
            'timeout' => 10,
            'connectionId' => (int) Db::get()->fetchOne('SELECT CONNECTION_ID()'),
        ]);

        $handle = proc_open([PHP_BINARY, '-r', $script], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
        if (!is_resource($handle)) {
            $this->fail('could not start the concurrent insert');
        }

        // wait until the row is inserted (and locked), the transaction is committed once the conflicting insert runs
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
