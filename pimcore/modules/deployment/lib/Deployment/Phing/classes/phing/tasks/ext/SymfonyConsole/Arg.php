<?php
require_once "phing/types/DataType.php";


/**
 * Implementation of console argument
 *
 * @author nuno costa <nuno@francodacosta.com>
 * @license GPL
 */
class Arg extends DataType
{
    private $name = null;
    private $value = null;
    private $quotes = false;

    /**
     * Gets the argment name
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the argument name
     * @param String $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the argument value
     * @return String
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the argument value
     * @param String $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Should the argument value be enclosed in double quotes
     * @return boolean
     */
    public function getQuotes()
    {
        return $this->quotes;
    }

    /**
     * Should the argument value be enclosed in double quotes
     * @param boolean $quotes
     */
    public function setQuotes( $quotes)
    {
        $this->quotes = $quotes;
    }

    /**
     * Transforms the argument object into a string, takes into consideration
     * the quotes and the argument value
     * @return String
     */
    public function __toString()
    {
        $name = "";
        $value = "";
        $quote = $this->getQuotes() ? '"' : '';

        if (!is_null($this->getValue())) {
            $value = $quote . $this->getValue() . $quote ;
        }

        if (!is_null($this->getName())) {
            $name = '--' . $this->getName();
        }

        if (strlen($name) > 0 && strlen($value) > 0) {
            $value = '=' . $value;
        }
        return $name . $value;
    }

}