<?php 
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
 * @copyright  Copyright © 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Event {

	//event types
	const EVENT_TYPE_FILE = 100;
    const EVENT_TYPE_FILE_MODIFIED = 101;
    const EVENT_TYPE_FILE_CREATED = 102;
    const EVENT_TYPE_FILE_DELETED = 103;
}