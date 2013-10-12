<?php

    namespace KsHtml\Form\Element;
    
    class SubmitElement extends \KsHtml\Form\Element
    {
        function __construct($value="")
        {
            parent::__construct("input");
            $this->setAttrs(
                array(
                    "type" => "submit"
                )
            );
            
            if($value) {
                $this->setAttr("value", $value);
            }
        }

        public function fillFromArray($arr, $filename=null)
        {
            throw new \Exception("Not implemented");
        }
    }

?>