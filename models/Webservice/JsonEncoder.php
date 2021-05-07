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

namespace Pimcore\Model\Webservice;

use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated
 */
class JsonEncoder
{
    /**
     * @param mixed $data
     * @param bool $returnData
     *
     * @return string
     */
    public function encode($data, $returnData = false)
    {
        $data = \Pimcore\Tool\Serialize::removeReferenceLoops($data);
        $data = json_encode($data, JSON_PRETTY_PRINT);

        if ($returnData) {
            return $data;
        } else {
            $response = new Response($data);
            $response->headers->set('Content-Type', 'application/json', true);
            $response->send();
            exit;
        }
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    public function decode($data)
    {
        $data = json_decode($data, true);

        return $data;
    }
}
