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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.report.broker");
pimcore.report.broker = {

    reports: {},
    groups: [
        {
            id: "other",
            name: t("other")
        }
    ],

    groupIds: [],

    addGroup: function (id, name, iconCls) {

        if (!in_array(id, this.groupIds)) {
            this.groups.push({
                id: id,
                name: t(name),
                iconCls: iconCls
            });
        }

        this.groupIds.push(id);
    },

    addReport: function (report, groupId) {
        if (!groupId) {
            groupId = "other";
        }

        if (typeof this.reports[groupId] != "object") {
            this.reports[groupId] = []
        }

        this.reports[groupId].push(report);
    }
};