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
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Webservice;

use Symfony\Component\HttpFoundation\Response;

class JsonEncoder
{
    /**
     * @param $data
     * @param bool $returnData
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
     * @param $data
     * @return mixed
     */
    public function decode($data)
    {
        $data = json_decode($data, true);

        return $data;
    }
}
