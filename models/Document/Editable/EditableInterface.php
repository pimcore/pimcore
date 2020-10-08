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
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Editable;

use Pimcore\Model\Document\PageSnippet;

interface EditableInterface
{
    /**
     * Return the data for direct output to the frontend, can also contain HTML code!
     *
     * @return string|void
     */
    public function frontend();

    /**
     * Return the data for the admin, can also contain HTML code!
     *
     * @return string|void
     */
    public function admin();

    /**
     * Get the current data stored for the element
     * this is used as general fallback for the methods getDataForResource(), admin(), getValue()
     *
     * @return mixed
     */
    public function getData();

    /**
     * Return the type of the element
     *
     * @return string
     */
    public function getType();

    /**
     * Receives the data from the editmode and convert this to the internal data in the object eg. image-id to Asset\Image
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromEditmode($data);

    /**
     * Receives the data from the resource, an convert to the internal data in the object eg. image-id to Asset\Image
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromResource($data);

    /**
     * Returns the current editable's data for web service export
     *
     * @deprecated
     *
     * @param PageSnippet|null $document
     * @param array $params
     * @abstract
     *
     * @return mixed
     */
    public function getForWebserviceExport($document = null, $params = []);

    /**
     * @return bool
     */
    public function isEmpty();
}

class_alias(EditableInterface::class, 'Pimcore\Model\Document\Tag\TagInterface');
