<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ckogler
 * Date: 29.05.13
 * Time: 12:31
 * To change this template use File | Settings | File Templates.
 */

class Pimcore_Tool_Lock {

    protected $pidFileName;
    protected $semaphoreHandle;
    protected $maxAge = null;

    public function __construct($options = array()){
        if(!$options['pidFileName']){
            $options['pidFileName'] = $this->getPidFileNameFromScriptPath();
        }else{
            $options['pidFileName'] = Pimcore_File::getValidFilename($options['pidFileName']);
        }

        foreach($options as $key => $value){
            $setter = "set" . ucfirst($key);
            if(method_exists($this,$setter)){
                $this->$setter($value);
            }
        }

        if(is_null($options['checkUserExecution']) || $options['checkUserExecution'] == true){
            Logger::info("checking executing user.");
            Pimcore_Tool_Console::checkExecutingUser();
        }
    }

    public function setMaxAge($seconds){
        $this->maxAge = $seconds;
    }

    public function getMaxAge(){
        return $this->maxAge;
    }

    protected function getSemaphoreFile(){
        $filename = PIMCORE_SYSTEM_TEMP_DIRECTORY .  "/" . $this->getPidFileName() . ".semaphore";
        return $filename;
    }

    protected function setPidFileName($pidFileName){
        $this->pidFileName = $pidFileName;
    }

    public function getPidFileName(){
        return $this->pidFileName;
    }

    protected function getPidFile(){
        $pidFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/". $this->getPidFileName() .".pid";
        return $pidFile;
    }

    public function writePid($data = array()){
        $data['creationDate'] = time();
        $data['processId'] = getmypid();
        file_put_contents($this->getPidFile(), serialize($data));
    }

    public function checkPid($message = "Exit: Cannot start process because there's already an active process running."){
        $pidFileData = $this->getPidFileContent();
        if($this->getMaxAge() && $pidFileData){
            if($pidFileData['creationDate'] + $this->getMaxAge() < time()){
                $this->removePidFile();
            }
        }

        if(file_exists($this->getPidFile())) {
            Logger::info($message);
            Logger::info("Pid File:" . $this->getPidFile());
            exit;
        }
        register_shutdown_function( array( $this, "shutdownHandler" ) );
    }

    public function releaseSemaphore(){
        fclose($this->semaphoreHandle);
    }

    public function getPidFileContent(){
        $pidFile = $this->getPidFile();
        if(is_readable($pidFile)){
            $content = file_get_contents($pidFile);
            if($content){
                return Pimcore_Tool_Serialize::unserialize($content);
            }
        }
    }



    public function semaphoreWait() {
        $filename = $this->getSemaphoreFile();
        Logger::info("waiting for lock... current time=" . time() . "\n");
        $curTime = microtime(true);
        $handle = fopen($filename, 'w') or die("Error opening file.");
        $this->semaphoreHandle = $handle;
        if (flock($this->semaphoreHandle, LOCK_EX)) {
            //nothing...
        } else {
            die("Could not lock file.");
        }
        $timeConsumed = round(microtime(true) - $curTime,3)*1000;
        Logger::info("Got Lock After: " . $timeConsumed . "msec\n");
    }

    public function shutdownHandler(){
        Logger::info("Shutdownhandler called\n remove PID file: " . $this->getPidFile());
        $lastError = error_get_last();
        if(is_array($lastError) && $lastError['type'] != E_NOTICE){
            print_r(error_get_last());
        }

        $this->removePidFile();
    }

    protected function removePidFile(){
        @unlink($this->getPidFile());
    }

    protected function getPidFileNameFromScriptPath(){
        global $argv;
        $paths = explode('/',$argv[0]);
        $fileName = array_pop($paths);
        return str_replace('.php','',$fileName);
    }
}