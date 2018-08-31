<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 08/05/2018
 * Time: 14:55
 */

namespace Pimcore\Translation\TranslationItemCollection;

class TranslationItem
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $id;

    /**
     * @var
     */
    private $element;


    /**
     * TranslationItem constructor.
     * @param string $type
     * @param string $id
     * @param object $element
     */
    public function __construct(string $type, string $id, $element)
    {
        $this->type = $type;
        $this->id = $id;
        $this->element = $element;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return object
     */
    public function getElement()
    {
        return $this->element;
    }


}
