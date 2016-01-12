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

    addReport: function (report, groupId, config) {
        if (!groupId) {
            groupId = "other";
        }

        if (typeof this.reports[groupId] != "object") {
            this.reports[groupId] = [];
        }

        this.reports[groupId].push({
            "class": report,
            config: config
        });
    }
};