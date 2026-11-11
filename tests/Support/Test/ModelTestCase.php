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

namespace Pimcore\Tests\Support\Test;

use Pimcore;
use Pimcore\Cache;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\Service;
use Pimcore\Tests\Support\ModelTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

abstract class ModelTestCase extends TestCase
{
    /**
     * @var ModelTester
     */
    protected $tester;

    private ?SessionInterface $session = null;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->needsDb()) {
            $this->setUpTestClasses();
        }
    }

    /**
     * Set up test classes before running tests
     */
    protected function setUpTestClasses(): void
    {
    }

    protected function needsDb(): bool
    {
        return true;
    }

    protected function buildSession(): SessionInterface
    {
        if (null === $this->session) {
            $this->session = new Session(new MockArraySessionStorage());

            $requestStack = Pimcore::getContainer()->get('request_stack');
            if (!$request = $requestStack->getCurrentRequest()) {
                $request = new Request();
                $requestStack->push($request);
            }

            $request->setSession($this->session);
        }

        return $this->session;
    }

    /**
     * Writes the object to the persistent cache (save() clears its cache tags, so getById() alone does not reliably
     * do it), clears the runtime cache and hands the object loaded from the cache to the callback. The fresh copy
     * force-loaded from the database for the consistency check is cached as well and stays in the runtime cache.
     */
    protected function forceSavingAndLoadingFromCache(Concrete $object, callable $callback): void
    {
        $cacheEnabled = Cache::isEnabled();
        if (!$cacheEnabled) {
            Cache::enable();
            Cache::getHandler()->setHandleCli(true);
        }

        try {
            //save object to cache
            $cacheKey = Service::getElementCacheTag('object', $object->getId());
            Cache::getHandler()->removeClearedTags($object->getCacheTags());
            Cache::save($object, $cacheKey, [], null, 9999, true);
            $this->assertNotFalse(Cache::load($cacheKey), 'Object must be stored in the persistent cache.');

            Cache\RuntimeCache::clear();
            //reload from cache and check again
            $objectCache = Concrete::getById($object->getId());

            //once more reload object from database to check consistency of
            //data object loaded from cache - see also https://github.com/pimcore/pimcore/issues/12290
            Concrete::getById($object->getId(), ['force' => true]);

            $callback($objectCache);
        } finally {
            if (!$cacheEnabled) {
                Cache::disable();
                Cache::getHandler()->setHandleCli(false);
            }
        }
    }
}
