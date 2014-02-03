<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 03.02.14
 * Time: 15:40
 */

class Document_Tag_Areablock_Item
{
    /**
     * @var Document_Page
     */
    protected $doc;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $index;

    /**
     * @param Document_Page $doc
     * @param               $name
     * @param               $index
     */
    public function __construct(Document_Page $doc, $name, $index)
    {
        $this->doc = $doc;
        $this->name = $name;
        $this->index = $index;
    }


    /**
     * @param $name
     *
     * @return Document_Tag
     */
    public function getElement($name)
    {
        $id = sprintf('%s%s%d', $name, $this->name, $this->index);
        $element = $this->doc->getElement( $id );
        $element->suffixes = array( $this->name );

        return $element;
    }

    /**
     * @param $func
     * @param $args
     *
     * @return Document_Tag_*|null
     */
    public function __call($func, $args)
    {
        $element = $this->getElement($args[0]);
        $class = 'Document_Tag_' . str_replace('get', '', $func);

        if(!strcasecmp(get_class($element), $class))
        {
            return $element;
        }
    }
}
