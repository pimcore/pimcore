<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004, Clay Loveless                                    |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// | This LICENSE is in the BSD license style.                            |
// | http://www.opensource.org/licenses/bsd-license.php                   |
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// |  * Redistributions of source code must retain the above copyright    |
// |    notice, this list of conditions and the following disclaimer.     |
// |                                                                      |
// |  * Redistributions in binary form must reproduce the above           |
// |    copyright notice, this list of conditions and the following       |
// |    disclaimer in the documentation and/or other materials provided   |
// |    with the distribution.                                            |
// |                                                                      |
// |  * Neither the name of Clay Loveless nor the names of contributors   |
// |    may be used to endorse or promote products derived from this      |
// |    software without specific prior written permission.               |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
// | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
// | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
// | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Author: Clay Loveless <clay@killersoft.com>                          |
// +----------------------------------------------------------------------+
//
// $Id$
//

/*
    In this example, we'll get a list of files in a repository, and
    link to their contents.
*/

error_reporting(E_ALL);
ini_set('display_errors', 'on');

require_once 'VersionControl/SVN.php';

// Default options
$base_url = 'https://github.com/pear/VersionControl_SVN/trunk';
$base_add = isset($_GET['base_add']) ? '/'.$_GET['base_add'] : '';
$cmd = isset($_GET['cmd']) ? $_GET['cmd'] : 'list';

try {
    // Create svn object with subcommands we'll want
    $svn = VersionControl_SVN::factory(array('list', 'cat'));


    // A quickie sample of browsing a Subversion repository
    if ($cmd == 'cat') {
        $file = $_GET['file'];
        $source = $svn->cat->run(array($base_url.$base_add.'/'.$file));
        if (substr($file, -4) == '.php') {
            highlight_string($source);
        } else {
            echo "<pre>$source</pre>\n";
        }

    } else {
        $list = $svn->list->run(array($base_url));
        foreach ($list['list'][0]['entry'] as $item) {
            if ($item['kind'] != 'file') {
                echo "<a href=\"example1.php?cmd=list&base_add={$base_add}/{$item['name']}\">{$item['name']}</a><br />\n";
            } else {
                echo "<a href=\"example1.php?cmd=cat&file={$item['name']}&base_add={$base_add}\">{$item['name']}</a><br />\n";
            }
        }
    }
} catch (VersionControl_SVN_Exception $e) {
    echo "<pre>\n";
    print_r($e->getMessage());
    echo "</pre>\n";
}
?>
