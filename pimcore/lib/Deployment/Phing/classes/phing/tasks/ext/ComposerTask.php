<?php

require_once "phing/Task.php";

/**
 * Composer Task
 * Run composer straight from phing
 *
 * @author nuno costa <nuno@francodacosta.com>
 * @license MIT
 *
 */
class ComposerTask extends \Task
{
    /**
     * @var string the path to php interperter
     */
    private $php = 'php';
    /**
     *
     * @var Array of Arg a collection of Arg objects
     */
    private $args = array();

    /**
     *
     * @var string the Composer command to execute
     */
    private $command = null;

    /**
     *
     * @var Commandline
     */
    private $commandLine =null;
    /**
     *
     * @var string path to Composer application
     */
    private $composer = 'composer.phar';

    public function __construct()
    {
        $this->commandLine = new Commandline();
    }

    /**
     * Sets the path to php executable.
     *
     * @param string $php
     */
    public function setPhp($php)
    {
        $this->php = $php;
    }

    /**
     * gets the path to php executable.
     *
     * @return string
     */
    public function getPhp()
    {
        return $this->php;
    }
    /**
     * sets the Composer command to execute
     * @param string $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * return the Composer command to execute
     * @return String
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * sets the path to Composer application
     * @param string $console
     */
    public function setComposer($console)
    {
        $this->composer = $console;
    }

    /**
     * returns the path to Composer application
     * @return string
     */
    public function getComposer()
    {
        return $this->composer;
    }

    /**
     * creates a nested arg task
     *
     * @return Arg Argument object
     */

    public function createArg()
    {
        return $this->commandLine->createArgument();
    }

    /**
     * Gets the command string to be executed
     * @return string
     */
    private function prepareCommand()
    {
        $this->commandLine->setExecutable($this->getPhp());

        $composerCommand = $this->commandLine->createArgument(true);
        $composerCommand->setValue($this->getCommand());

        $composerPath = $this->commandLine->createArgument(true);
        $composerPath->setValue($this->getCOmposer());

    }
    /**
     * executes the synfony consile application
     */
    public function main()
    {

        $this->prepareCommand();
        $this->log("executing $this->commandLine");

        $composerFile = new SplFileInfo($this->getComposer());
        if (false === $composerFile->isExecutable()
                || false === $composerFile->isFile()) {
            throw new BuildException(sprintf('Composer binary not found, path is "%s"', $composerFile));
        }

        $return = 0;
        passthru($this->commandLine, $return);

        if ($return > 0) {
            throw new BuildException("Composer execution failed");
        }
    }
}
