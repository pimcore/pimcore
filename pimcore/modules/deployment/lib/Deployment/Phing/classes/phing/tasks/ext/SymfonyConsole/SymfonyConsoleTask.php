<?php
require_once "phing/Task.php";
require_once dirname(__FILE__) . "/Arg.php";
/**
 * Symfony Console Task
 * @author nuno costa <nuno@francodacosta.com>
 * @license GPL
 *
 */
class SymfonyConsoleTask extends Task
{

    /**
     *
     * @var Array of Arg a collection of Arg objects
     */
    private $args = array();

    /**
     *
     * @var string the Symfony console command to execute
     */
    private $command = null;

    /**
     *
     * @var string path to symfony console application
     */
    private $console = 'php app/console';


    /**
     * sets the symfony console command to execute
     * @param string $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * return the symfony console command to execute
     * @return String
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * sets the path to symfony console application
     * @param string $console
     */
    public function setConsole($console)
    {
        $this->console = $console;
    }

    /**
     * returns the path to symfony console application
     * @return string
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * appends an arg tag to the arguments stack
     *
     * @return Arg Argument object
     */

    public function createArg()
    {
        $num = array_push($this->args, new Arg());
        return $this->args[$num-1];
    }

    /**
     * return the argumments passed to this task
     * @return array of Arg()
     */
    public function getArgs()
    {
        return $this->args;
    }


    /**
     * Gets the command string to be executed
     * @return string
     */
    public function getCmdString() {
        $cmd = array(
                $this->console,
                $this->command,
                implode(' ', $this->args)
        );
        $cmd = implode(' ', $cmd);
        return $cmd;
    }
    /**
     * executes the synfony consile application
     */
    public function main()
    {
        $cmd = $this->getCmdString();

        $this->log("executing $cmd");
        passthru ($cmd);
    }
}
