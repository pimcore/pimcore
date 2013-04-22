<?php
/**
 * $Id: a4825de14746285d97af9d435215ec8400406fa7 $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

require_once 'phing/tasks/ext/phpcpd/formatter/PHPCPDResultFormatter.php';

/**
 * Prints plain text output of phpcpd run
 *
 * @package phing.tasks.ext.phpcpd.formatter
 * @author  Benjamin Schultz <bschultz@proqrent.de>
 * @version $Id: a4825de14746285d97af9d435215ec8400406fa7 $
 */
class DefaultPHPCPDResultFormatter extends PHPCPDResultFormatter
{

    /**
     * Processes a list of clones.
     *
     * @param object $clones
     * @param Project $project
     * @param boolean $useFile
     * @param PhingFile|null $outfile
     */
    public function processClones($clones, Project $project, $useFile = false, $outFile = null)
    {
        if (get_class($clones) == 'PHPCPD_CloneMap') {
            $logger = new PHPCPD_TextUI_ResultPrinter();
        } else {
            $logger = new \SebastianBergmann\PHPCPD\TextUI\ResultPrinter();
        }

        // default format goes to logs, no buffering
        ob_start();
        $logger->printResult($clones, $project->getBaseDir(), true);
        $output = ob_get_contents();
        ob_end_clean();

        if (!$useFile || empty($outFile)) {
            echo $output;
        } else {
            file_put_contents($outFile->getPath(), $output);
        }
    }
}