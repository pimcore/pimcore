<?php

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

namespace Pimcore\Model\Element;

use League\Flysystem\UnableToDeleteDirectory;
use Pimcore\Model;
use Pimcore\Tool\Storage;

/**
 * @method \Pimcore\Model\Element\Recyclebin\Dao getDao()
 *
 * @internal
 */
final class Recyclebin extends Model\AbstractModel
{
    public function flush()
    {
        $this->getDao()->flush();
        $bin = Storage::get('recycle_bin');
        try {
            $bin->deleteDirectory('/');
        } catch (UnableToDeleteDirectory) {
            // it seems there is a problem with AW3 adapter due the trim
            // https://github.com/thephpleague/flysystem-aws-s3-v3/blob/d8de61ee10b6a607e7996cff388c5a3a663e8c8a/AwsS3V3Adapter.php#L236

            $listing = $bin->listContents('/', true);

            /** @var \League\Flysystem\StorageAttributes $item */
            foreach ($listing as $item) {
                $bin->delete($item->path());
            }
        }
    }
}
