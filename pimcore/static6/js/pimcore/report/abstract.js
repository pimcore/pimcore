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

pimcore.registerNS("pimcore.report.abstract");
pimcore.report.abstract = Class.create({

    initialize: function (reportPanel, type, reference, config) {
        this.reportPanel = reportPanel;
        this.type = type;
        this.reference = reference;
        this.config = config;

        this.addPanel();
    },

    getName: function () {
        return "no name set";
    },

    getIconCls: function () {
        return "";
    },

    matchType: function (type) {
        return false;
    },

    getPanel: function () {
        console.log("You have to implement the getPanel() method.");
    },

    addPanel: function () {
        this.reportPanel.addReport(this.getPanel());
    },

    matchTypeValidate: function (type, validTypes) {
        if (typeof type == "string") {
            return in_array(type, validTypes);
        }
        else if (typeof type == "object") {
            for (var i = 0; i < type.length; i++) {
                if (in_array(type[i], validTypes)) {
                    return true;
                }
            }
        }
        return false;
    }
});