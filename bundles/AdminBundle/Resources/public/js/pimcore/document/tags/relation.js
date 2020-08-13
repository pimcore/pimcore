/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 *
 * @deprecated since v6.8 and will be removed in 7. Use {@link pimcore.document.editables.relation} instead.
 */

pimcore.registerNS("pimcore.document.tags.relation");
pimcore.document.tags.relation = Class.create(pimcore.document.editables.relation, {
});

// @TODO BC layer, to be removed in v7.0
pimcore.document.tags.href = pimcore.document.tags.relation;