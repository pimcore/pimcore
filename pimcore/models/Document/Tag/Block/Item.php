<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 03.02.14
 * Time: 15:36
 */

/**
 * Class Document_Tag_Block_Item
 *
 * @method Document_Tag_Link getLink() getLink(string $name)
 */
class Document_Tag_Block_Item
{
    /**
     * @var Document_Page
     */
    protected $doc;

    /**
     * @var int
     */
    protected $index;

    /**
     * @var string[]
     */
    protected $suffixes = array();

    /**
     * @param Document_Page $doc
     * @param int           $index
     * @param array         $suffixes
     */
    public function __construct(Document_Page $doc, $index, array $suffixes)
    {
        $this->doc = $doc;
        $this->index = $index;
        $this->suffixes = $suffixes;
    }


    /**
     * @param $name
     *
     * @return Document_Tag
     */
    public function getElement($name)
    {
        $root = $name . implode('_', $this->suffixes);
        foreach($this->suffixes as $item)
        {
            if(preg_match('#[^\d]{1}(?<index>[\d]+)$#i', $item, $match))
            {
                $root .= $match['index'] . '_';
            }
        }
        $root .= $this->index;
        $id = $root;

        $element = $this->doc->getElement( $id );
        $element->suffixes = $this->suffixes;

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
        $class = 'Document_Tag_' . str_replace('get','',$func);

        if(!strcasecmp(get_class($element), $class))
        {
            return $element;
        }
    }
}
