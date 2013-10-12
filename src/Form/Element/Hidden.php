<?php

    namespace KsHtml\Form\Element;
    
    class Hidden extends \KsHtml\Form\Element
    {
        function __construct($name, $value="")
        {
            parent::__construct("input", $name);
            
            $this->setAttrs(
                array(
                    "name" => $name,
                    "type" => "hidden",
                    "value" => $value
                )
            );
        }

        public function getTag()
        {
            if(!$this->getAttr("value") && $this->value) {
                $this->setAttr("value", $this->value);
            }

            return parent::getTag();
        }
    }

?>