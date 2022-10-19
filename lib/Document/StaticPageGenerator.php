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

namespace Pimcore\Document;

use Pimcore\Document\Renderer\DocumentRenderer;
use Pimcore\Http\Request\Resolver\StaticPageResolver;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Tool\Storage;
use Symfony\Component\Lock\LockFactory;

class StaticPageGenerator
{
    /**
     * @var DocumentRenderer
     */
    protected $documentRenderer;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    public function __construct(DocumentRenderer $documentRenderer, LockFactory $lockFactory)
    {
        $this->documentRenderer = $documentRenderer;
        $this->lockFactory = $lockFactory;
    }

    /**
     * @param Document\PageSnippet $document
     *
     * @return string
     */
    public function getStoragePath($document)
    {
        $path = $document->getRealFullPath();

        if ($document instanceof Document\Page && $document->getPrettyUrl()) {
            $path = $document->getPrettyUrl();
        } elseif ($path === '/') {
            $path = '/%home';
        }

        return $path . '.html';
    }

    /**
     * @param Document\PageSnippet $document
     * @param array $params
     *
     * @return bool
     */
    public function generate($document, $params = [])
    {
        $storagePath = $this->getStoragePath($document);

        $storage = Storage::get('document_static');
        $startTime = microtime(true);

        $lockKey = 'document_static_' . $document->getId() . '_' . md5($storagePath);

        $lock = $this->lockFactory->createLock($lockKey);

        if ($params['is_cli'] ?? false) {
            $lock->acquire(true);
        }

        try {
            if (!$response = $params['response'] ?? false) {
                $response = $this->documentRenderer->render($document, [
                    'pimcore_static_page_generator' => true,
                    StaticPageResolver::ATTRIBUTE_PIMCORE_STATIC_PAGE => true,
                ]);
            }

            $storage->write($storagePath, $response);
        } catch (\Exception $e) {
            Logger::debug('Error generating static Page ' . $storagePath .': ' . $e->getMessage());

            return false;
        }

        Logger::debug('Static Page ' . $storagePath . ' generated in ' . (microtime(true) - $startTime) . ' seconds');

        if ($params['is_cli'] ?? false) {
            $lock->release();
        }

        return true;
    }

    /**
     * @param Document\PageSnippet $document
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function remove($document)
    {
        $storagePath = $this->getStoragePath($document);
        $storage = Storage::get('document_static');

        $storage->delete($storagePath);
    }

    /**
     * @param Document\PageSnippet $document
     *
     * @return bool
     */
    public function pageExists($document)
    {
        $storagePath = $this->getStoragePath($document);
        $storage = Storage::get('document_static');

        return $storage->fileExists($storagePath);
    }

    /**
     * @param Document\PageSnippet $document
     *
     * @return int|null
     */
    public function getLastModified($document)
    {
        $storagePath = $this->getStoragePath($document);
        $storage = Storage::get('document_static');

        if ($storage->fileExists($storagePath)) {
            return $storage->lastModified($storagePath);
        }

        return null;
    }
}
