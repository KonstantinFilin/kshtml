<?php

    namespace KsHtml\Form\Element;

    class Checkbox extends \KsHtml\Form\Element
    {
        function __construct($name, $label)
        {
            parent::__construct("input", $name, $label);
            $this->setAttrs(
                array(
                    "name" => $name,
                    "type" => "checkbox"
                )
            );
        }

        public function getTag()
        {
            if(intval($this->value)) {
                $this->setAttr("checked", "checked");
            }

            return parent::getTag();
        }

        public function getPostedValue()
        {
            return !empty ($_REQUEST[$this->elementName]) && $_REQUEST[$this->elementName] ? 1 : 0;
        }
    }

?>
