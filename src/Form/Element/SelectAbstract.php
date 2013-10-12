<?php

    namespace KsHtml\Form\Element;

    /*
     * To change this template, choose Tools | Templates
     * and open the template in the editor.
     */

    /**
     * Description of SelectAbstractElement
     *
     * @author ksf
     */
    abstract class SelectAbstract extends \KsHtml\Form\Element\AbstractList
    {
        function __construct($name, $label)
        {
            parent::__construct("select", $name, $label, true);
            $this->disableValueEncoding();
        }

        protected function fillTagOptions()
        {
            foreach($this->availableValues as $value => $label) {
                $optionTag = new \KsHtml\Tag("option", true);

                $optionTag->disableValueEncoding();
                $optionTag->setAttr("value", $value);
                $optionTag->setContent($label);

                if($this->isActiveValue($value)) {
                    $optionTag->setAttr("selected", "selected");
                }

                $this->addChildTag($optionTag);
            }
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
