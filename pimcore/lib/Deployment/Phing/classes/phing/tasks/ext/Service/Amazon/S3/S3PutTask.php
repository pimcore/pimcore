<?php
/*
 *  $Id: 8d435567a86c67e90bc1ef4e54c7affa78b97fc0 $
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

require_once dirname(dirname(__FILE__)) . '/S3.php';

/**
 * Stores an object on S3
 * 
 * @version $Id: 8d435567a86c67e90bc1ef4e54c7affa78b97fc0 $
 * @package phing.tasks.ext
 * @author Andrei Serdeliuc <andrei@serdeliuc.ro>
 * @extends Service_Amazon_S3
 */
class S3PutTask extends Service_Amazon_S3
{
    /**
     * File we're trying to upload
     *
     * (default value: null)
     * 
     * @var string
     * @access protected
     */
    protected $_source = null;

    /**
	 * Content we're trying to upload
	 *
	 * The user can specify either a file to upload or just a bit of content
	 *
	 * (default value: null)
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_content = null;
	
	/**
     * Collection of filesets
     * Used for uploading multiple files
     * 
     * (default value: array())
     * 
     * @var array
     * @access protected
     */
    protected $_filesets = array();
	
	/**
	 * Whether to try to create buckets or not
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access protected
	 */
	protected $_createBuckets = false;
	
	/**
	 * File ACL
	 * Use to set the permission to the uploaded files
	 *
	 * (default value: 'private')
	 *
	 * @var string
	 * @access protected
	 */
	protected $_acl = 'private';
	
	/**
	 * File content type
	 * Use this to set the content type of your static files
	 * Set contentType to "auto" if you want to autodetect the content type based on the source file extension
	 *
	 * (default value: 'binary/octet-stream')
	 *
	 * @var string
	 * @access protected
	 */
	protected $_contentType = 'binary/octet-stream';
	
	/**
	 * Extension content type mapper
	 *
	 * @var array
	 * @access protected
	 */
	protected $_extensionContentTypeMapper = array(
		'js'	=> 'application/x-javascript',
		'css'	=> 'text/css',
		'html'	=> 'text/html',
		'gif'	=> 'image/gif',
		'png'	=> 'image/png',
		'jpg'	=> 'image/jpeg',
		'jpeg'	=> 'image/jpeg',
		'txt'	=> 'text/plain'
	);
    
    public function setSource($source)
    {
        if(!is_readable($source)) {
            throw new BuildException('Source is not readable: ' . $source);
        }

        $this->_source = $source;
    }
    
    public function getSource()
    {
        if($this->_source === null) {
            throw new BuildException('Source is not set');
        }
        
        return $this->_source;
    }

	public function setContent($content)
	{
		if(empty($content) || !is_string($content)) {
			throw new BuildException('Content must be a non-empty string');
		}
		
		$this->_content = $content;
	}
	
	public function getContent()
	{
		if($this->_content === null) {
			throw new BuildException('Content is not set');
		}
		
		return $this->_content;
	}

	public function setObject($object)
	{
		if(empty($object) || !is_string($object)) {
			throw new BuildException('Object must be a non-empty string');
		}
		
		$this->_object = $object;
	}
	
	public function getObject()
	{
		if($this->_object === null) {
			throw new BuildException('Object is not set');
		}
		
		return $this->_object;
	}
	
	public function setAcl($permission)
	{
		$valid_acl = array('private', 'public-read', 'public-read-write', 'authenticated-read');
		if(empty($permission) || !is_string($permission) || !in_array($permission, $valid_acl)) {
			throw new BuildException('Object must be one of the following values: ' . implode('|', $valid_acl));
		}
		$this->_acl = $permission;
	}

	public function getAcl()
	{
		return $this->_acl;
	}
	
	public function setContentType($contentType) 
	{
		$this->_contentType = $contentType;
	}

	public function getContentType()
	{
		if($this->_contentType === 'auto') {
			$ext = strtolower(substr(strrchr($this->getSource(), '.'), 1));
			if(isset($this->_extensionContentTypeMapper[$ext])) {
				return $this->_extensionContentTypeMapper[$ext];
			} else {
				return 'binary/octet-stream';
			}
		} else {
			return $this->_contentType;
		}
	}

	public function setCreateBuckets($createBuckets)
    {
        $this->_createBuckets = (bool) $createBuckets;
    }

	public function getCreateBuckets()
    {
        return (bool) $this->_createBuckets;
    }

	/**
     * creator for _filesets
     * 
     * @access public
     * @return FileSet
     */
    public function createFileset()
    {
        $num = array_push($this->_filesets, new FileSet());
        return $this->_filesets[$num-1];
    }

	/**
     * getter for _filesets
     * 
     * @access public
     * @return array
     */
    public function getFilesets()
    {
        return $this->_filesets;
    }

    /**
	 * Determines what we're going to store in the object
	 * 
	 * If _content has been set, this will get stored,
	 * otherwise, we read from _source
	 *
	 * @access public
	 * @return string
	 */
	public function getObjectData()
	{
		try {
			$content = $this->getContent();
		} catch(BuildException $e) {
			$source = $this->getSource();
			
			if(!is_file($source)) {
                throw new BuildException('Currently only files can be used as source');
			}
			
			$content = file_get_contents($source);
		}
		
		return $content;
	}
    
    /**
     * Store the object on S3
     * 
     * @access public
     * @return void
     */
    public function execute()
    {
		if(!$this->isBucketAvailable()) {
			if(!$this->getCreateBuckets()) {
				throw new BuildException('Bucket doesn\'t exist and createBuckets not specified');
			} else{
				if(!$this->createBucket()) {
					throw new BuildException('Bucket cannot be created');
				}
			}
		}
		
		// Filesets take precedence
		if(!empty($this->_filesets)) {
			$objects = array();
			
			foreach($this->_filesets as $fs) {
	            if(!($fs instanceof FileSet)) {
	                continue;
	            }

				$ds = $fs->getDirectoryScanner($this->getProject());
				$objects = array_merge($objects, $ds->getIncludedFiles());
			}
			
			$fromDir = $fs->getDir($this->getProject())->getAbsolutePath();
			
			foreach($objects as $object) {
				$this->saveObject($object, file_get_contents($fromDir . DIRECTORY_SEPARATOR . $object));
			}
			
			return true;
		}
		
		$this->saveObject($this->getObject(), $this->getObjectData());
    }

	protected function saveObject($object, $data)
	{
		$object = $this->getObjectInstance($object);
		$object->data = $data;
		$object->acl = $this->getAcl();
		$object->contentType = $this->getContentType();
		$object->save();
		
		if(!$this->isObjectAvailable($object->key)) {
			throw new BuildException('Upload failed');
		}
	}
}