<?php

    namespace KsHtml\Form\Element;
    
    class Select extends \KsHtml\Form\Element\SelectAbstract
    {
        public function getTag()
        {
            if($this->emptyValueLabel) {
                $this->availableValues =
                    array("" => $this->emptyValueLabel) +
                    $this->availableValues;
            }

            $this->setAttr("name", $this->elementName);
            $this->fillTagOptions();

            return parent::getTag();
        }

        protected function isActiveValue($value)
        {
            return $value == $this->value;
        }
    }

?>