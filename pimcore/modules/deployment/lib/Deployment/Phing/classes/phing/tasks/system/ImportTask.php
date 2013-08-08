<?php
/*
 *  $Id: 88eafadfcd59c0ce28f122926b7d5706b807a8e3 $
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

require_once 'phing/Task.php';
require_once 'phing/system/io/FileSystem.php';
require_once 'phing/system/io/PhingFile.php';
require_once 'phing/parser/ProjectConfigurator.php';

/**
 * Imports another build file into the current project.
 *
 * Targets and properties of the imported file can be overrridden 
 * by targets and properties of the same name declared in the importing file. 
 *
 * The imported file will have a new synthetic property of 
 * "phing.file.<projectname>" declared which gives the full path to the 
 * imported file. Additionally each target in the imported file will be 
 * declared twice: once with the normal name and once with "<projectname>." 
 * prepended. The "<projectname>.<targetname>" synthetic targets allow the 
 * importing file a mechanism to call the imported files targets as 
 * dependencies or via the <phing> or <phingcall> task mechanisms.
 *
 * @author Bryan Davis <bpd@keynetics.com>
 * @version $Id: 88eafadfcd59c0ce28f122926b7d5706b807a8e3 $
 * @package phing.tasks.system
 */
class ImportTask extends Task {

  /**
   * @var FileSystem
   */
  protected $fs;

  /**
   * @var PhingFile
   */
  protected $file = null;

  /**
   * @var bool
   */
  protected $optional = false;

  /**
   * Initialize task.
   * @return void
   */
  public function init () {
    $this->fs = FileSystem::getFileSystem();
  } //end init


  /**
   * Set the file to import.
   * @param string $f Path to file
   * @return void
   */
  public function setFile ($f) {
    $this->file = $f;
  }

  /**
   * Is this include optional?
   * @param bool $opt If true, do not stop the build if the file does not 
   * exist
   * @return void
   */
  public function setOptional ($opt) {
    $this->optional = $opt;
  }

  /**
   * Parse a Phing build file and copy the properties, tasks, data types and 
   * targets it defines into the current project.
   *
   * @return void
   */
  public function main () {
    if (!isset($this->file)) {
      throw new BuildException("Missing attribute 'file'");
    }

    $file = new PhingFile($this->file);
    if (!$file->isAbsolute()) {
      $file = new PhingFile($this->project->getBasedir(), $this->file);
    }
    if (!$file->exists()) {
      $msg = "Unable to find build file: {$file->getPath()}";
      if ($this->optional) {
        $this->log($msg . '... skipped');
        return;
      } else {
        throw new BuildException($msg);
      }
    }

    $ctx = $this->project->getReference("phing.parsing.context");
    $cfg = $ctx->getConfigurator();
    if (null !== $cfg && $cfg->isParsing()) {
      // because there isn't a top level implicit target in phing like there is 
      // in Ant 1.6, we will be called as soon as our xml is parsed. This isn't 
      // really what we want to have happen. Instead we will register ourself 
      // with the parse context to be called at the end of the current file's 
      // parse phase.
      $cfg->delayTaskUntilParseEnd($this);

    } else {
      // Import xml file into current project scope
      // Since this is delayed until after the importing file has been 
      // processed, the properties and targets of this new file may not take 
      // effect if they have alreday been defined in the outer scope.
      $this->log("Importing configuration from {$file->getName()}", Project::MSG_VERBOSE);
      ProjectConfigurator::configureProject($this->project, $file);
      $this->log("Configuration imported.", Project::MSG_VERBOSE);
    }
  } //end main

} //end ImportTask
