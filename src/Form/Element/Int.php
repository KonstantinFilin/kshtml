<?php

    namespace KsHtml\Form\Element;
    
    class Int extends \KsHtml\Form\Element\String
    {
        private $allowNegative = false;

        function __construct($name, $label)
        {
            parent::__construct($name, $label);
        }

        public function setAllowNegative($allowNegative)
        {
            $this->allowNegative = $allowNegative;
        }

        public function getTag()
        {
            $pattern = $this->allowNegative ? "/^-?[\d]+$/" : "/^[\d]+$/";
            $pattern2 = $this->allowNegative ? "/[^-\d]/" : "/[^\d]/";
            $this->setAttr("onkeyup", "if(!this.value.match(" . $pattern . ")) this.value = this.value.replace(" . $pattern2 . ", '');");
            return parent::getTag();
        }

        public function getPostedValue()
        {
            $ret = parent::getPostedValue();
            $ret = intval($ret);
            
            if(!$this->allowNegative) {
                $ret = abs($ret);
            }
            
            return $ret;
        }
    }

?>
