<?php

    namespace KsHtml\Form;
    
    abstract class Element extends \KsHtml\Tag
    {
        protected $label;
        protected $error;
        protected $description;
        protected $elementName;
        protected $value;

        function __construct($tagName, $name=null, $label="", $hasClosingTag=false)
        {
            if($tagName) {
                parent::__construct($tagName, $hasClosingTag);
            }

            $this->elementName = $name;
            $this->label = $label;
            $this->error = "";
            $this->description = "";
        }

        public function getError()
        {
            return $this->error;
        }

        /**
         *
         * @param String $error
         * @return HtmlForm_Element
         */
        public function setError($error)
        {
            $this->error = $error;
            return $this;
        }

        public function setValue($value)
        {
            $this->value = $value;
        }

        public function getTag()
        {
            return parent::__toString();
        }

        public function getLabel()
        {
            return $this->label;
        }

        /**
         *
         * @param String $label
         * @return HtmlForm_Element
         */
        public function setLabel($label)
        {
            $this->label = $label;
            return $this;
        }

        public function getDescription()
        {
            return $this->description;
        }

        /**
         *
         * @param String $description
         * @return HtmlForm_Element
         */
        public function setDescription($description)
        {
            $this->description = $description;
            return $this;
        }

        public function getName()
        {
            return $this->elementName;
        }

        public function getValue()
        {
            return $this->value;
        }

        public function getPostedValue()
        {
            return isset($_POST[$this->elementName]) ? $_POST[$this->elementName] : null;
        }

        /**
         *
         * @param array $arr
         * @param String $filename
         * @return HtmlForm_StringElement
         */
        public function fillFromArray($arr)
        {
            if(!empty($arr["description"])) {
                $this->setDescription($arr["description"]);
            }

            if(!empty($arr["value"])) {
                $this->setValue($arr["value"]);
            }
        }
    }

?>