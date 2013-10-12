<?php

    namespace KsHtml\Form\Element;

    class AbstractList extends \KsHtml\Form\Element
    {
        protected $availableValues;
        protected $value;
        protected $emptyValueLabel;
        protected $skipItemsCheck;

        function __construct($tagName, $name, $label, $hasClosingTag=false)
        {
            parent::__construct($tagName, $name, $label, $hasClosingTag);
            $this->value = array();
            $this->availableValues = array();
            $this->emptyValueLabel = "-= Не выбрано =-";
            $this->skipItemsCheck = false;
        }

        public function setAvailableValues($availableValues)
        {
            $this->availableValues = $availableValues;
            return $this;
        }
        
        public function setSkipItemsCheck()
        {
            $this->skipItemsCheck = true;
        }

        public function getValue()
        {
            return $this->value;
        }

        public function setValue($value)
        {
            $this->value = $value;
            return $this;
        }

        public function setEmptyValueLabel($emptyValueLabel)
        {
            $this->emptyValueLabel = $emptyValueLabel;
        }

        public function getPostedValue()
        {
            $arrNamePattern = "/([\w_\d]+)\[([\w_\d]+)\]/i";

            if(preg_match($arrNamePattern, $this->elementName, $matches) &&
                    !empty ($matches[1]) && !empty($matches[2])) {
                if(isset($_REQUEST[$matches[1]][$matches[2]]) &&
                    ($this->skipItemsCheck || in_array($_REQUEST[$matches[1]][$matches[2]], array_keys($this->availableValues)))
                    
                    ) {

                    return $_REQUEST[$matches[1]][$matches[2]];
                }
            } else {
                if(isset($_REQUEST[$this->elementName]) &&
                    ($this->skipItemsCheck || in_array($_REQUEST[$this->elementName], array_keys($this->availableValues)))) {

                    return $_REQUEST[$this->elementName];
                }
            }

            return null;
        }

        public function fillFromArray($arr, $filename=null)
        {
            parent::fillFromArray($arr);

            if(!empty($arr["available_values"]) && is_array($arr["available_values"])) {
                $this->setAvailableValues($arr["available_values"]);
            }
        }
    }

?>