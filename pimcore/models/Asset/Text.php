<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset;

use Pimcore\Cache;
use Pimcore\Model;
use Pimcore\Logger;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Text extends Model\Asset
{

    /**
     * @var string
     */
    public $type = "text";

    public function getText($page = null)
    {
        if (preg_match("/\.?(txt|csv|xml)$/", $this->getFilename())) {
            $cacheKey = "asset_text_text_" . $this->getId() . "_" . ($page ? $page : "all");

            if (!$text = Cache::load($cacheKey)) {
                $text = file_get_contents($this->getFileSystemPath());
                Cache::save($text, $cacheKey, $this->getCacheTags(), null, 99, true); // force cache write
            }

            return $text;
        } else {
            Logger::error("Couldn't get text out of text asset " . $this->getRealFullPath() . " not supported extension");
        }

        return null;
    }
}
