<?php

    namespace KsHtml\Form\Element;
    
    class Radio extends \KsHtml\Form\Element\AbstractList
    {
        private $delimeter;

        function __construct($name, $label)
        {
            parent::__construct(null, $name, $label);
            $this->setAttr("name", $name);
            $this->name = $name;
            $this->delimeter = "<br />";
        }

        public function setDelimeter($delimeter)
        {
            $this->delimeter = $delimeter;
            return $this;
        }

        public function getTag()
        {
            $tags = array();
            
            if($this->emptyValueLabel) {
                $this->availableValues =
                    array("" => $this->emptyValueLabel) +
                    $this->availableValues;
            }

            foreach($this->availableValues as $val=>$label) {

                $tag = new \KsHtml\Tag("input");
                $tag->setAttr("value", $val)
                    ->setAttr("name", $this->getAttr("name"))
                    ->setAttr("type", "radio");

                if($this->isActiveValue($val)) {
                    $tag->setAttr("checked");
                }

                $tags[] = "<label>".
                          $tag->__toString().
                          " ".htmlspecialchars($label).
                          "</label>";
            }

            return implode($this->delimeter, $tags);
        }

        public function getName()
        {
            return $this->name;
        }
        
        public function setValue($value)
        {
            $this->value = $value;
        }

        private function isActiveValue($value)
        {
            return $this->value == $value || (!$this->value && !$value);
        }
    }

?>