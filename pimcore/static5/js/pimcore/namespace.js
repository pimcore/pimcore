/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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