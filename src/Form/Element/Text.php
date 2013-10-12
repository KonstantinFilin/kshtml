<?php

    namespace KsHtml\Form\Element;
    
    class Text extends \KsHtml\Form\Element
    {
        private $rows;
        private $cols;
      //  private $value;

        function __construct($name, $label)
        {
            parent::__construct("textarea", $name, $label, true);
            $this->setAttr("name", $name);
            $this->rows = 7;
        }

        public function setRows($rows)
        {
            $this->rows = $rows;
        }

        public function setCols($cols)
        {
            $this->cols = $cols;
        }

        public function setValue($value)
        {
            $this->value = $value;
        }

        public function getValue() {
            return $this->value;
        }

        public function getTag()
        {
            $this->setAttr("rows", $this->rows)
                 ->setAttr("cols", $this->cols)
                 ->setContent($this->value);
            return parent::getTag();
        }

        public function fillFromArray($arr, $filename = null)
        {
            parent::fillFromArray($arr, $filename);

            if(!empty($arr["rows"]) && intval($arr["rows"])) {
                $this->setRows(intval($arr["rows"]));
            }

            if(!empty($arr["cols"]) && intval($arr["cols"])) {
                $this->setCols(intval($arr["cols"]));
            }
        }
    }

?>