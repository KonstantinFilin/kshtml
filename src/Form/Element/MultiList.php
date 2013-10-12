<?php

    namespace KsHtml\Form\Element;
    /*
     * To change this template, choose Tools | Templates
     * and open the template in the editor.
     */

    /**
     * Description of MultiListElement
     *
     * @author ksf
     */
    class MultiList extends \KsHtml\Form\Element\AbstractList
    {
        function __construct($name, $label)
        {
            parent::__construct(null, $name, $label);
            $this->setAttr("name", $name);
            $this->disableValueEncoding();
            $this->label = $label;
            $this->delimeter = "<br />";
        }

        public function getTag()
        {
            $tags = array();
// d($this->value); die;
            foreach($this->availableValues as $value => $label) {
                $tag = new \KsHtml\Tag("input");
                $tag->setAttr("value", $value)
                    ->setAttr("name", $this->getAttr("name") . "[]")
                    ->setAttr("type", "checkbox");

                if($this->isActiveValue($value)) {
                    $tag->setAttr("checked");
                }

                $tags[] = "<label>" .
                          $tag->__toString() .
                          " " . htmlspecialchars($label) .
                          "</label>";
            }

            return implode($this->delimeter, $tags);
        }

        private function isActiveValue($value)
        {
            return in_array($value, $this->value);
        }

        public function fillFromArray($arr, $filename=null)
        {
            if(!empty($arr["value"]) && !is_array($arr["value"])) {
                $message = sprintf("Значение параметра value элемента %s должно быть массивом", $arr["name"]);
                throw new \Exception($message);
            }

            parent::fillFromArray($arr);
        }

        public function setValue($value)
        {
            $this->value = is_array($value) ? $value : explode(",", $value);
        }

        public function getPostedValue()
        {
            $vals = array();
            $arrNamePattern = "/([\w_\d]+)\[([\w_\d]+)\]/i";

            if(preg_match($arrNamePattern, $this->elementName, $matches) &&
                    !empty ($matches[1]) && !empty($matches[2])) {
                foreach ($_REQUEST[$matches[1]][$matches[1]] as $value) {
                    if(isset($_REQUEST[$matches[1]][$matches[2]]) &&
                    in_array($_REQUEST[$matches[1]][$matches[2]], array_keys($this->availableValues))) {
                        $vals[] = $value;
                    }
                }
            } else {
                if(isset($_REQUEST[$this->elementName]) &&
                    is_array($_REQUEST[$this->elementName])) {

                    foreach ($_REQUEST[$this->elementName] as $value) {
                        if(in_array($value, array_keys($this->availableValues))) {
                            $vals[] = $value;
                        }
                    }
                }
            }

            return $vals;
        }
    }

?>
