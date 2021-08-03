/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

// some global helper functions
pimcore.registerNS("pimcore.helpers.date.x");

/**
 * @param {Date} [date]
 * @return {int}
 */
pimcore.helpers.date.getOffsetToServer = function (date) {
    if (!date) {
        date = new Date();
    }
    return pimcore.settings.timezone_offset + (date.getTimezoneOffset() * 60);
}


/**
 * @param {int} timestamp
 * @return {int}
 */
pimcore.helpers.date.shiftTimestampFromServerToLocalTimezone = function (timestamp) {
    return timestamp + pimcore.helpers.date.getOffsetToServer();
}

/**
 * @param {int} timestamp
 * @return {int}
 */
pimcore.helpers.date.shiftTimestampFromLocalToServerTimezone = function (timestamp) {
    return timestamp - pimcore.helpers.date.getOffsetToServer();
}

/**
 * @param {int|string|null} timestamp
 * @param {boolean} [shiftTimezone=true] Shift timezone from Pimcore server to local browser?
 * @return {Date|null}
 */
pimcore.helpers.date.convertServerToBrowserDate = function (timestamp, shiftTimezone) {
    if (timestamp === null || timestamp === undefined) {
        return null;
    }

    timestamp = intval(timestamp);
    if (shiftTimezone !== false) {
        timestamp = pimcore.helpers.date.shiftTimestampFromServerToLocalTimezone(timestamp);
    }
    return new Date(timestamp * 1000);
}

/**
 *
 * @param {Date|int|null} date
 * @param {boolean} [shiftTimezone=true] Shift timezone from local browser server to Pimcore server?
 * @return {int|null}
 */
pimcore.helpers.date.convertBrowserToServerTimestamp = function (date, shiftTimezone) {
    if (date === null || date === undefined) {
        return null;
    }

    var timestamp = date;
    if (typeof date.getTime === 'function') {
        timestamp = date.getTime();
    }
    timestamp /= 1000;

    if (shiftTimezone !== false) {
        timestamp = pimcore.helpers.date.shiftTimestampFromLocalToServerTimezone(timestamp);
    }

    return timestamp;
}
