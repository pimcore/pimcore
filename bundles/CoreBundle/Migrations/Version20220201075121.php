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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToMoveFile;
use Pimcore\Tool\Storage;

final class Version20220201075121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $storage = Storage::get('thumbnail');

        try {
            //remove source parent folder thumbnails
            $thumbnailFiles = $storage->listContents('/', true)->filter(function(StorageAttributes $attributes) {
                return $attributes->isFile() && preg_match('/^image-thumb__\d+__/', basename($attributes->path()));
            });
            /** @var StorageAttributes $thumbnailFile */
            foreach ($thumbnailFiles as $thumbnailFile) {
                $targetPath = preg_replace('/^image-thumb__(\d+)__(.+)$/', 'image-thumb__$1/$2', $thumbnailFile->path());
                $storage->move($thumbnailFile->path(), $targetPath);
            }
        } catch (UnableToMoveFile $e) {
        }

        $oldThumbnailDirectories = $storage->listContents('/', false)->filter(function (StorageAttributes $attributes) {
            return $attributes->isDir() && preg_match('/^image-thumb__\d+__/', basename($attributes->path()));
        });
        /** @var StorageAttributes $oldThumbnailDirectory */
        foreach ($oldThumbnailDirectories as $oldThumbnailDirectory) {
            $storage->deleteDirectory($oldThumbnailDirectory->path());
        }
    }

    public function down(Schema $schema): void
    {
        $storage = Storage::get('thumbnail');

        try {
            //remove source parent folder thumbnails
            $thumbnailFiles = $storage->listContents('/', true)->filter(fn(StorageAttributes $attributes) => ($attributes->isFile() && preg_match('/image-thumb__\d+\//', $attributes->path())));
            /** @var StorageAttributes $thumbnailFile */
            foreach ($thumbnailFiles as $thumbnailFile) {
                $targetPath = preg_replace('/^image-thumb__(\d+)\/(.+)$/', 'image-thumb__$1__$2', $thumbnailFile->path());
                $storage->move($thumbnailFile->path(), $targetPath);
            }
        } catch (UnableToMoveFile $e) {
        }
    }
}
