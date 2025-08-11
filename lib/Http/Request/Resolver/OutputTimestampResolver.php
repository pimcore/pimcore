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

namespace Pimcore\Http\Request\Resolver;

/**
 * @internal
 *
 * Gets/sets the timestamp for which output should be delivered. Default is current timestamp, but timestamp
 * might be set to a date in future for preview purposes
 */
class OutputTimestampResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_PIMCORE_OUTPUT_TIMESTAMP = '_pimcore_output_timestamp';

    protected bool $timestampWasQueried = false;

    /**
     * Gets timestamp for with the output should be rendered to
     *
     */
    public function getOutputTimestamp(): int
    {
        $request = $this->getMainRequest();
        $timestamp = $request->attributes->get(self::ATTRIBUTE_PIMCORE_OUTPUT_TIMESTAMP);

        if (!$timestamp) {
            $timestamp = time();
            $this->setOutputTimestamp($timestamp);
        }

        //flag to store if timestamp was queried during request
        $this->timestampWasQueried = true;

        return $timestamp;
    }

    /**
     * Sets output timestamp to given value
     *
     */
    public function setOutputTimestamp(int $timestamp): void
    {
        $this->getMainRequest()->attributes->set(self::ATTRIBUTE_PIMCORE_OUTPUT_TIMESTAMP, $timestamp);
    }

    /**
     * Returns if timestamp was queried during request at least once
     *
     */
    public function timestampWasQueried(): bool
    {
        return $this->timestampWasQueried;
    }
}
