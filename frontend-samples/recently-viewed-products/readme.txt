/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


Sample code for recently-viewed-products implementation

1) make sure you product detail page has a input field with the id js-productId
    <input type="hidden" value="<?= $this->product->getCanonicalId() ?>" id="js-productId" />

2) make sure you product detail page has a container with the id js-recent-products (parent container for recently viewed products)
   <div class="relatedbox" id="js-recent-products"> </div>

3) make sure the ajax_route is configured

4) add content of AjaxServiceController to a Controller, copy recently-viewed-products.php to includes directory and modify the template

5) include recently-viewed-products.js to detail page, modify route for post request if necessary