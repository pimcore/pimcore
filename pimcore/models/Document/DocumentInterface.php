<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Document;

use Pimcore\Model;

interface DocumentInterface extends Model\Element\ElementInterface {

    /**
     * @param string $path
     * @return Model\Document
     */
    public static function getByPath($path);

    /**
     * @param string $id
     * @return Model\Document|Model\Document\Page|Model\Document\Folder|Model\Document\Snippet|Model\Document\Link
     */
    public static function getConcreteById($id);

    /**
     * @param string $path
     * @return Model\Document|Model\Document\Page|Model\Document\Folder|Model\Document\Snippet|Model\Document\Link
     */
    public static function getConcreteByPath($path);


    /**
     * @return void
     */
    public function save();

    /**
     * @return void
     */
    public function delete();
}
