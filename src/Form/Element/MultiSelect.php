<?php

    namespace KsHtml\Form\Element;

    /*
     * To change this template, choose Tools | Templates
     * and open the template in the editor.
     */

    /**
     * Description of MultiSelectElement
     *
     * @author ksf
     */
    class MultiSelect extends \KsHtml\Form\Element\SelectAbstract
    {
        public function getTag()
        {
            $this->setAttr("multiple", "multiple");
            $this->setAttr("name", $this->elementName . "[]");
            $this->fillTagOptions();
            return parent::getTag();
        }

        protected function isActiveValue($value)
        {
            return in_array($value, $this->value);
        }

        public function getPostedValue()
        {
            $vals = array();

            if(isset($_REQUEST[$this->elementName]) &&
                is_array($_REQUEST[$this->elementName])) {

                foreach ($_REQUEST[$this->elementName] as $value) {
                    if(in_array($value, array_keys($this->availableValues))) {
                        $vals[] = $value;
                    }
                }
            }

            return $vals;
        }

        public function fillFromArray($arr, $filename = null)
        {
            parent::fillFromArray($arr, $filename);

            if(!empty ($arr["size"])) {
                $this->setAttr("size", $arr["size"]);
            }
        }
    }

?>
