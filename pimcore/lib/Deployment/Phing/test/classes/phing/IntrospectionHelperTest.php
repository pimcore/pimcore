<?php
/*
 *  $Id: 108e8daee36637c84371ab9d94573a3f40add5b5 $
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

require_once 'PHPUnit/Framework/TestCase.php';
include_once 'phing/tasks/system/condition/OsCondition.php';

/**
 * testcases for phing.IntrospectionHelper.
 * 
 * @author Hans Lellelid <hans@xmpl.org> (Phing)
 * @author Stefan Bodewig <stefan.bodewig@epost.de> (Ant)
 * @version $Id$
 * @package phing
 */
class IntrospectionHelperTest extends PHPUnit_Framework_TestCase {

    /** @var Project */
    private $p;
    
    public function setUp() {
        $this->p = new Project();
        $this->p->setBasedir(DIRECTORY_SEPARATOR);
    }
    
    /**
     *
     * @throws BuildException
     */
    public function testAddText()  {
        $ih = IntrospectionHelper::getHelper('Exception');
        try {
            $ih->addText($this->p, new Exception(), "test");
            $this->fail("Exception doesn\'t support addText");
        } catch (BuildException $be) {
        }

        $ih = IntrospectionHelper::getHelper('IHProjectComponent');
        $ih->addText($this->p, new IHProjectComponent(), "test");
    }

    public function testSupportsCharacters() {
        $ih = IntrospectionHelper::getHelper('Exception');
        $this->assertTrue(!$ih->supportsCharacters(), "String doesn\'t support addText");
        $ih = IntrospectionHelper::getHelper('IHProjectComponent');
        $this->assertTrue($ih->supportsCharacters(), "IHProjectComponent supports addText");
    }    
    
    public function testElementCreators() {
        
        try {
            $ihtmp = IntrospectionHelper::getHelper('IHCreatorFail1');
            $this->fail("create cannot take param");
        } catch (BuildException $be) {}

        try {
            $ihtmp = IntrospectionHelper::getHelper('IHCreatorFail2');
            $this->fail("no class hint for add");
        } catch (BuildException $be) {}

        try {
            $ihtmp = IntrospectionHelper::getHelper('IHCreatorFail3');
            $this->fail("no class hint for addconfigured");
        } catch (BuildException $be) {}
        
        $ih = IntrospectionHelper::getHelper('IHProjectComponent');
        $this->assertEquals("test", $ih->createElement($this->p, new IHProjectComponent(), "one"));
       
    }
    
    /*
    public function testGetNestedElements() {
        Hashtable h = new Hashtable();
        h.put("six", java.lang.String.class);
        h.put("thirteen", java.lang.StringBuffer.class);
        h.put("fourteen", java.lang.StringBuffer.class);
        h.put("fifteen", java.lang.StringBuffer.class);
        IntrospectionHelper $ih = IntrospectionHelper::getHelper(get_class($this));
        Enumeration enum = ih.getNestedElements();
        while (enum.hasMoreElements()) {
            String name = (String) enum.nextElement();
            Class expect = (Class) h.get(name);
            assertNotNull("Support for "+name+" in IntrospectioNHelperTest?",
                          expect);
            $this->assertEquals("Return type of "+name, expect, ih.getElementType(name));
            h.remove(name);
        }
        $this->assertTrue("Found all", h.isEmpty());
    }

    public function createOne() {
        return "test";
    }
    /*
    public function testAttributeSetters() {
        $ih = IntrospectionHelper::getHelper(get_class($this));
        try {
            $ih->setAttribute($p, $this, "one", "test");
            $this->fail("setOne doesn't exist");
        } catch (BuildException $be) {
        }
        try {
            $ih->setAttribute($p, $this, "two", "test");
            $this->fail("setTwo returns non void");
        } catch (BuildException be) {
        }
        try {
            ih.setAttribute(p, this, "three", "test");
            $this->fail("setThree takes no args");
        } catch (BuildException be) {
        }
        try {
            ih.setAttribute(p, this, "four", "test");
            $this->fail("setFour takes two args");
        } catch (BuildException be) {
        }
        try {
            ih.setAttribute(p, this, "five", "test");
            $this->fail("setFive takes array arg");
        } catch (BuildException be) {
        }
        try {
            ih.setAttribute(p, this, "six", "test");
            $this->fail("Project doesn't have a String constructor");
        } catch (BuildException be) {
        }
        ih.setAttribute(p, this, "seven", "2");
        try {
            ih.setAttribute(p, this, "seven", "3");
            $this->fail("2 shouldn't be equals to three");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        ih.setAttribute(p, this, "eight", "2");
        try {
            ih.setAttribute(p, this, "eight", "3");
            $this->fail("2 shouldn't be equals to three - as int");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        ih.setAttribute(p, this, "nine", "2");
        try {
            ih.setAttribute(p, this, "nine", "3");
            $this->fail("2 shouldn't be equals to three - as Integer");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        ih.setAttribute(p, this, "ten", "2");
        try {
            ih.setAttribute(p, this, "ten", "3");
            $this->fail(projectBasedir+"2 shouldn't be equals to "+projectBasedir+"3");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        ih.setAttribute(p, this, "eleven", "2");
        try {
            ih.setAttribute(p, this, "eleven", "on");
            $this->fail("on shouldn't be false");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        ih.setAttribute(p, this, "twelve", "2");
        try {
            ih.setAttribute(p, this, "twelve", "on");
            $this->fail("on shouldn't be false");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        ih.setAttribute(p, this, "thirteen", "org.apache.tools.ant.Project");
        try {
            ih.setAttribute(p, this, "thirteen", "org.apache.tools.ant.ProjectHelper");
            $this->fail("org.apache.tools.ant.Project shouldn't be equal to org.apache.tools.ant.ProjectHelper");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        try {
            ih.setAttribute(p, this, "thirteen", "org.apache.tools.ant.Project2");
            $this->fail("org.apache.tools.ant.Project2 doesn't exist");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof ClassNotFoundException);
        }
        ih.setAttribute(p, this, "fourteen", "2");
        try {
            ih.setAttribute(p, this, "fourteen", "on");
            $this->fail("2 shouldn't be equals to three - as StringBuffer");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        ih.setAttribute(p, this, "fifteen", "abcd");
        try {
            ih.setAttribute(p, this, "fifteen", "on");
            $this->fail("o shouldn't be equal to a");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        ih.setAttribute(p, this, "sixteen", "abcd");
        try {
            ih.setAttribute(p, this, "sixteen", "on");
            $this->fail("o shouldn't be equal to a");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        ih.setAttribute(p, this, "seventeen", "17");
        try {
            ih.setAttribute(p, this, "seventeen", "3");
            $this->fail("17 shouldn't be equals to three");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        ih.setAttribute(p, this, "eightteen", "18");
        try {
            ih.setAttribute(p, this, "eightteen", "3");
            $this->fail("18 shouldn't be equals to three");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
        ih.setAttribute(p, this, "nineteen", "19");
        try {
            ih.setAttribute(p, this, "nineteen", "3");
            $this->fail("19 shouldn't be equals to three");
        } catch (BuildException be) {
            $this->assertTrue(be.getException() instanceof AssertionFailedError);
        }
    }

    public void testGetAttributes() {
        Hashtable h = new Hashtable();
        h.put("seven", java.lang.String.class);
        h.put("eight", java.lang.Integer.TYPE);
        h.put("nine", java.lang.Integer.class);
        h.put("ten", java.io.File.class);
        h.put("eleven", java.lang.Boolean.TYPE);
        h.put("twelve", java.lang.Boolean.class);
        h.put("thirteen", java.lang.Class.class);
        h.put("fourteen", java.lang.StringBuffer.class);
        h.put("fifteen", java.lang.Character.TYPE);
        h.put("sixteen", java.lang.Character.class);
        h.put("seventeen", java.lang.Byte.TYPE);
        h.put("eightteen", java.lang.Short.TYPE);
        h.put("nineteen", java.lang.Double.TYPE);

        h.put("name", java.lang.String.class);

        IntrospectionHelper $ih = IntrospectionHelper::getHelper(get_class($this));
        Enumeration enum = ih.getAttributes();
        while (enum.hasMoreElements()) {
            String name = (String) enum.nextElement();
            Class expect = (Class) h.get(name);
            assertNotNull("Support for "+name+" in IntrospectionHelperTest?",
                          expect);
            $this->assertEquals("Type of "+name, expect, ih.getAttributeType(name));
            h.remove(name);
        }
        h.remove("name");
        $this->assertTrue("Found all", h.isEmpty());
    }

    public function setTwo($s) {
        return 0;
    }

    public void setThree() {}

    public void setFour(String s1, String s2) {}

    public void setFive(String[] s) {}

    public void setSix(Project p) {}

    public void setSeven(String s) {
        $this->assertEquals("2", s);
    }

    public void setEight(int i) {
        $this->assertEquals(2, i);
    }

    public void setNine(Integer i) {
        $this->assertEquals(2, i.intValue());
    }

    public void setTen(File f) {
        if (Os.isFamily("unix")) { 
            $this->assertEquals(projectBasedir+"2", f.getAbsolutePath());
        } else if (Os.isFamily("netware")) {
            $this->assertEquals(projectBasedir+"2", f.getAbsolutePath().toLowerCase(Locale.US));
        } else {
            $this->assertEquals(":"+projectBasedir+"2", f.getAbsolutePath().toLowerCase(Locale.US).substring(1));
        }
    }

    public void setEleven(boolean b) {
        $this->assertTrue(!b);
    }

    public void setTwelve(Boolean b) {
        $this->assertTrue(!b.booleanValue());
    }

    public void setThirteen(Class c) {
        $this->assertEquals(Project.class, c);
    }

    public void setFourteen(StringBuffer sb) {
        $this->assertEquals("2", sb.toString());
    }

    public void setFifteen(char c) {
        $this->assertEquals(c, 'a');
    }

    public void setSixteen(Character c) {
        $this->assertEquals(c.charValue(), 'a');
    }

    public void setSeventeen(byte b) {
        $this->assertEquals(17, b);
    }

    public void setEightteen(short s) {
        $this->assertEquals(18, s);
    }

    public void setNineteen(double d) {
        $this->assertEquals(19, d, 1e-6);
    }
    */
}// IntrospectionHelperTest

// These are sample project components

class IHProjectComponent {

    public function addText($text) {        
    }
    
    public function createOne() {
        return "test";
    }    
}


// These classes force failure
// 

class IHCreatorFail1 {
    /**
     * cannot take param!
     */
    function createBlah($param) {    
    }
}

class IHCreatorFail2 {

    /**
     * no class hint!
     */
    function addBlah($blah) {
    }
}

class IHCreatorFail3 {

    /**
     * no class hint!
     */
    function addConfiguredBlah($blah) {
    }    
}


class IHFail4 {

    /**
     * 2 params!
     */
    function setBlah($blah, $blah2) {
    }
}

class IHFail5 {

    /**
     * no params!
     */
    function setBlah() {
    }
    
}
