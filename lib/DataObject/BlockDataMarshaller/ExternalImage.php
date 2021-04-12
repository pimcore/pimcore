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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\BlockDataMarshaller;

use Pimcore\Marshaller\MarshallerInterface;

class ExternalImage implements MarshallerInterface
{
    /**
     * { @inheritdoc }
     */
    public function marshal($value, $params = [])
    {
        if (is_array($value)) {
            return new \Pimcore\Model\DataObject\Data\ExternalImage($value['url']);
        }

        return null;
    }

    /**
     * { @inheritdoc }
     */
    public function unmarshal($value, $params = [])
    {
        if ($value instanceof \Pimcore\Model\DataObject\Data\ExternalImage) {
            return [
                'url' => $value->getUrl(),
            ];
        }

        return null;
    }
}
