<?php
/*
 * $Id: bfc85569ff437f8ee382c9dee6fc8eb55ecb9839 $
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

/**
 * Track the current state of the Xml parse operation.
 *
 * @author    Bryan Davis <bpd@keynetics.com>
 * @version   $Id$
 * @access    public
 * @package   phing.parser
 */
class PhingXMLContext {

    /**
     * Constructor
     * @param $project the project to which this antxml context belongs to
     */
    public function __construct ($project) {
      $this->project = $project;
    }

    /** The project to configure. */
    private $project;

    private $configurators = array();

    public function startConfigure ($cfg) {
      $this->configurators[] = $cfg;
    }

    public function endConfigure () {
      array_pop($this->configurators);
    }

    public function getConfigurator () {
      $l = count($this->configurators);
      if (0 == $l) {
        return null;
      } else {
        return $this->configurators[$l - 1];
      }
    }

    /** Impoerted files */
    private $importStack = array();

    public function addImport ($file) {
      $this->importStack[] = $file;
    }

    public function getImportStack () {
      return $this->importStack;
    }

    /**
     * find out the project to which this context belongs
     * @return project
     */
    public function getProject() {
        return $this->project;
    }

} //end PhingXMLContext
