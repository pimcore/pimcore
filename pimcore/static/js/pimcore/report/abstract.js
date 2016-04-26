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