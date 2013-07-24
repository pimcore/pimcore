<?php
/*
 *  $Id: 73bc68bf5c60646fcf3b905d34cc2dc0f9d596f8 $
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
 
include_once 'phing/Task.php';

/**
 * Send an e-mail message
 *
 * <mail tolist="user@example.org" subject="build complete">The build process is a success...</mail> 
 * 
 * @author   Michiel Rook <mrook@php.net>
 * @author   Francois Harvey at SecuriWeb (http://www.securiweb.net)
 * @version  $Id: 73bc68bf5c60646fcf3b905d34cc2dc0f9d596f8 $
 * @package  phing.tasks.ext
 */
class MailTask extends Task
{
    protected $tolist = null;
    protected $subject = null;
    protected $msg = null;
    protected $from = null;
    
    protected $filesets = array();
    
    public function main()
    {
        if (empty($this->from)) {
            throw new BuildException('Missing "from" attribute');
        }
        
        $this->log('Sending mail to ' . $this->tolist);
        
        if (!empty($this->filesets)) {
            @require_once 'Mail.php';
            @require_once 'Mail/mime.php';
            
            if (!class_exists('Mail_mime')) {
                throw new BuildException('Need the PEAR Mail_mime package to send attachments');
            }
            
            $mime = new Mail_mime(array('text_charset' => 'UTF-8'));
            $hdrs = array(
            	'From'    => $this->from,
            	'Subject' => $this->subject
            );
            $mime->setTXTBody($this->msg);
            
            foreach ($this->filesets as $fs) {
                $ds = $fs->getDirectoryScanner($this->project);
                $fromDir  = $fs->getDir($this->project);
                $srcFiles = $ds->getIncludedFiles();

                foreach ($srcFiles as $file) {
                    $mime->addAttachment($fromDir . DIRECTORY_SEPARATOR . $file, 'application/octet-stream');
                }
            }
            
            $body = $mime->get();
            $hdrs = $mime->headers($hdrs);
            
            $mail = Mail::factory('mail');
            $mail->send($this->tolist, $hdrs, $body);
        } else {
            mail($this->tolist, $this->subject, $this->msg, "From: {$this->from}\n");
        }
    }

    /**
     * Setter for message
     */
    public function setMsg($msg)
    {
        $this->setMessage($msg);
    }

    /**
     * Alias setter
     */
    public function setMessage($msg)
    {
        $this->msg = (string) $msg;
    }
    
    /**
     * Setter for subject
     */
    public function setSubject($subject)
    {
        $this->subject = (string) $subject;
    }

    /**
     * Setter for tolist
     */
    public function setToList($tolist)
    {
        $this->tolist = $tolist;
    }
    
    /**
     * Alias for (deprecated) recipient
     */
    public function setRecipient($recipient)
    {
        $this->tolist = (string) $recipient;
    }

    /**
     * Alias for to
     */
    public function setTo($to)
    {
        $this->tolist = (string) $to;
    }
        
    /**
     * Supports the <mail>Message</mail> syntax.
     */
    public function addText($msg)
    {
        $this->msg = (string) $msg;
    }
    
    /**
     * Sets email address of sender
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }
    
    /**
     * Adds a fileset
     */
    public function createFileSet()
    {
        $fileset = new FileSet();
        $this->filesets[] = $fileset;
        return $fileset;
    }
}
