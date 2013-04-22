<?php
/*
 * $Id: f73d7c67a353cf16f048af3ba013d84ec726a926 $
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
 
require_once 'phing/parser/AbstractHandler.php';

/**
 * The target handler class.
 *
 * This class handles the occurance of a <target> tag and it's possible
 * nested tags (datatypes and tasks).
 *
 * @author    Andreas Aderhold <andi@binarycloud.com>
 * @copyright  2001,2002 THYRELL. All rights reserved
 * @version   $Id: f73d7c67a353cf16f048af3ba013d84ec726a926 $
 * @package   phing.parser
 */
class TargetHandler extends AbstractHandler {

    /**
     * Reference to the target object that represents the currently parsed
     * target.
     * @var object the target instance
     */
    private $target;

    /**
     * The phing project configurator object
     * @var ProjectConfigurator
     */
    private $configurator;

    /**
     * Constructs a new TargetHandler
     *
     * @param  object  the ExpatParser object
     * @param  object  the parent handler that invoked this handler
     * @param  object  the ProjectConfigurator object
     */
    function __construct(AbstractSAXParser $parser, AbstractHandler $parentHandler, ProjectConfigurator $configurator) {
        parent::__construct($parser, $parentHandler);
        $this->configurator = $configurator;      
    }

    /**
     * Executes initialization actions required to setup the data structures
     * related to the tag.
     * <p>
     * This includes:
     * <ul>
     * <li>creation of the target object</li>
     * <li>calling the setters for attributes</li>
     * <li>adding the target to the project</li>
     * <li>adding a reference to the target (if id attribute is given)</li>
     * </ul>
     *
     * @param  string  the tag that comes in
     * @param  array   attributes the tag carries
     * @throws ExpatParseException if attributes are incomplete or invalid
     */
    function init($tag, $attrs) {
        $name = null;
        $depends = "";
        $ifCond = null;
        $unlessCond = null;
        $id = null;
        $description = null;
        $isHidden = false;

        foreach($attrs as $key => $value) {
            if ($key==="name") {
                $name = (string) $value;
            } else if ($key==="depends") {
                $depends = (string) $value;
            } else if ($key==="if") {
                $ifCond = (string) $value;
            } else if ($key==="unless") {
                $unlessCond = (string) $value;
            } else if ($key==="id") {
                $id = (string) $value;
            } else if ($key==="hidden") {
                $isHidden = ($value == 'true' || $value == '1') ? true : false;
            } else if ($key==="description") {
                $description = (string)$value;
            } else {
                throw new ExpatParseException("Unexpected attribute '$key'", $this->parser->getLocation());
            }
        }

        if ($name === null) {
            throw new ExpatParseException("target element appears without a name attribute",  $this->parser->getLocation());
        }

        // shorthand
        $project = $this->configurator->project;

        // check to see if this target is a dup within the same file
        if (isset($this->configurator->getCurrentTargets[$name])) {
          throw new BuildException("Duplicate target: $targetName",  
              $this->parser->getLocation());
        }

        $this->target = new Target();
        $this->target->setName($name);
        $this->target->setHidden($isHidden);
        $this->target->setIf($ifCond);
        $this->target->setUnless($unlessCond);
        $this->target->setDescription($description);
        // take care of dependencies
        if (strlen($depends) > 0) {
            $this->target->setDepends($depends);
        }

        $usedTarget = false;
        // check to see if target with same name is already defined
        $projectTargets = $project->getTargets();
        if (isset($projectTargets[$name])) {
          $project->log("Already defined in main or a previous import, " .
            "ignore {$name}", Project::MSG_VERBOSE);
        } else {
          $project->addTarget($name, $this->target);
          if ($id !== null && $id !== "") {
            $project->addReference($id, $this->target);
          }
          $usedTarget = true;
        }

        if ($this->configurator->isIgnoringProjectTag() && 
            $this->configurator->getCurrentProjectName() != null && 
            strlen($this->configurator->getCurrentProjectName()) != 0) {
          // In an impored file (and not completely
          // ignoring the project tag)
          $newName = $this->configurator->getCurrentProjectName() . "." . $name;
          if ($usedTarget) {
            // clone needs to make target->children a shared reference
            $newTarget = clone $this->target;
          } else {
            $newTarget = $this->target;
          }
          $newTarget->setName($newName);
          $ct = $this->configurator->getCurrentTargets();
          $ct[$newName] = $newTarget;
          $project->addTarget($newName, $newTarget);
        }
    }

    /**
     * Checks for nested tags within the current one. Creates and calls
     * handlers respectively.
     *
     * @param  string  the tag that comes in
     * @param  array   attributes the tag carries
     */
    function startElement($name, $attrs) {
        // shorthands
        $project = $this->configurator->project;
        $types = $project->getDataTypeDefinitions();

        if (isset($types[$name])) {
            $th = new DataTypeHandler($this->parser, $this, $this->configurator, $this->target);
            $th->init($name, $attrs);
        } else {
            $tmp = new TaskHandler($this->parser, $this, $this->configurator, $this->target, null, $this->target);
            $tmp->init($name, $attrs);
        }
    }
    
    /**
     * Checks if this target has dependencies and/or nested tasks.
     * If the target has neither, show a warning.
     */
    protected function finished()
    {
        if (!count($this->target->getDependencies()) && !count($this->target->getTasks())) {
            $this->configurator->project->log("Warning: target '" . $this->target->getName() .
                "' has no tasks or dependencies", Project::MSG_WARN);
        }
    }
}
