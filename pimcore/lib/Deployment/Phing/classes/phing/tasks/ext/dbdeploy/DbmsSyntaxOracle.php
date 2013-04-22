<?php
/*
 *  $Id: be6f99d787b94f7f2c1c8e359def3d465386bba9 $
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
 *  Utility class for generating necessary server-specific SQL commands
 *
 *  @author   Luke Crouch at SourceForge (http://sourceforge.net)
 *  @version  $Id$
 *  @package  phing.tasks.ext.dbdeploy
 */

class DbmsSyntaxOracle extends DbmsSyntax 
{
    public function applyAttributes($db)
    {
        $db->setAttribute(PDO::ATTR_ATTR_CASE, PDO::CASE_LOWER);
    }
    
    public function generateTimestamp()
    {
        return "(sysdate - to_date('01-JAN-1970','DD-MON-YYYY')) * (86400)";
    }
}

