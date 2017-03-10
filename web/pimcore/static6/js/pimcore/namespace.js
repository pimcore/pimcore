/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

if (!pimcore) {
    var pimcore = {};
}


pimcore.registerNS = function(namespace) {
    var spaces = namespace.split(".");

    // create main space
    if (typeof window[spaces[0]] != "object") {
        window[spaces[0]] = {};
    }
    var currentLevel = window[spaces[0]];

    // create all subspaces
    for (var i = 1; i < (spaces.length - 1); i++) {
        if (typeof currentLevel[spaces[i]] != "object") {
            currentLevel[spaces[i]] = {};
        }
        currentLevel = currentLevel[spaces[i]];
    }
    return currentLevel;
};