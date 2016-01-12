/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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