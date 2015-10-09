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


Sample code for Grid with ajax-reloading

1) make sure the ajax_route is configured

2) add content of AjaxServiceController to a Controller, copy grid.php and reload-products.php to views modify the template
   (eventually also copy content of _shared folder to a location in your views directory)

3) copy are productgrid to area directory - modify templates if necessary

4) make sure all js files are available on the included paths, or modify the include paths in the scripts

5) make sure filters are build like in the example, or modify the filter methods resetFilter, selectFilter, submitForm