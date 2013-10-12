<?php

    namespace KsHtml\Form\Element;

    /*
     * To change this template, choose Tools | Templates
     * and open the template in the editor.
     */

    /**
     * Description of HtmlForm_StringElement
     *
     * @author ksf
     */
    class String extends \KsHtml\Form\Element
    {
        const TYPE_TEXT = "text";
        const TYPE_FILE = "file";
        
        function __construct($name, $label)
        {
            parent::__construct("input", $name, $label);

            $this->value = "";

            $this->setAttrs(
                array(
                    "name" => $name,
                    "type" => self::TYPE_TEXT
                )
            );
        }
        
        public function getTag() {
            $this->setAttr("value", $this->value);
            return parent::getTag();
        }
    }

?>
