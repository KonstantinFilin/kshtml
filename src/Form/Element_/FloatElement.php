<?php

    class HtmlForm_FloatElement extends HtmlForm_StringElement
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
            //$pattern = $this->allowNegative ? "/^-?[\d]+(\.[\d]+)?$/" : "/^[\d]+(\.[\d]+)?$/";
            $pattern = $this->allowNegative ? "/^-?[\d]+(\.[\d]+)?$/" : "/^[\d]+(\.[\d]+)?$/";
            $pattern2 = $this->allowNegative ? "/[^-\.\d]/" : "/[^\.\d]/";
            $this->setAttr("onkeyup", "this.value = this.value.replace(',', '.'); if(!this.value.match(" . $pattern . ")) this.value = this.value.replace(" . $pattern2 . ", '');");
            return parent::getTag();
        }

        public function getPostedValue()
        {
            $ret = parent::getPostedValue();
            $ret = floatval($ret);

            if(!$this->allowNegative && $ret<0) {
                return null;
            }

            return $ret;
        }

        public function validate()
        {
            $value = $this->getPostedValue();

            if(!$this->allowNegative && $value<0) {
                return false;
            }
        }
    }

?>
