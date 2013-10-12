<?php

    namespace KsHtml;
    
    class Form extends \KsHtml\Tag 
    {
        protected $elements;
        protected $submits;
        protected $hiddenElements;
        protected $dataModel;
        protected $dataFilters;
        protected $globalErrors;
        protected $elementErrors;

        function __construct($name)
        {
            parent::__construct("form", true);

            $this->attributes = array(
                "action" => "",
                "method" => "POST",
                "enctype" => "application/x-www-form-urlencoded",
                "name" => "form_" . $name
            );

            $this->setAttrs($this->attributes);

            $this->elements = array();
            $this->hiddenElements = array();
            $this->submits = array();
            $this->dataModel = array();
            $this->elementErrors = array();
            $this->globalErrors = array();
        }

        function setElement(\KsHtml\Form\Element $el)
        {
            if($el instanceof \KsHtml\Form\Element\Hidden) {
                $this->hiddenElements[$el->getName()] = $el;
            } else {
                $this->elements[$el->getName()] = $el;
                
                if($el instanceof \KsHtml\Form\Element\File) {
                    $this->setUploadable();
                }
            }

            return $this;
        }

        function addGlobalError($error)
        {
            $this->globalErrors[] = $error;
            return $this;
        }

        function addElementError($elementName, $error)
        {
            $this->elementErrors[$elementName] = $error;
            return $this;
        }

        function hasErrors()
        {
            return (bool) ($this->globalErrors || $this->elementErrors);
        }

        function getErrors()
        {
            return array(
                "global" => $this->getGlobalErrors(),
                "elements" => $this->getElementErrors()
            );
        }

        function getGlobalErrors()
        {
            return $this->globalErrors;
        }

        function getElementErrors()
        {
            return $this->elementErrors;
        }

        public function getFormName()
        {
            return $this->getAttr("name");
        }

        public function setFormName($name)
        {
            $this->setAttr("name", $name);
            return $this;
        }

        /**
         *
         * @param String $name
         * @return HtmlForm_Element
         */
        function getElement($name)
        {
            // d($name); die;
            return !empty($this->elements[$name]) &&
                    $this->elements[$name] instanceof HtmlForm_Element ?
                    $this->elements[$name] : null;
        }

        function getElementNames()
        {
            return array_keys($this->elements);
        }
        
        function getElements()
        {
            return $this->elements;
        }

        function setUploadable()
        {
            $this->setAttr("enctype", "multipart/form-data");
            return $this;
        }

        public function setElementValues($values)
        {
            foreach ($this->elements as $k => $element) {
                if ($element instanceof \KsHtml\Form\Element) {
                    if (!empty($values[$element->getName()])) {
                        $this->elements[$k]->setValue($values[$element->getName()]);
                    }
                }
            }

            return $this;
        }

        public function removeElement($name)
        {
            unset($this->elements[$name]);
        }
        
        public function addSubmitElement($label, $name="")
        {
            $subm = new HtmlForm_SubmitElement($label);
            if($name) {
                $subm->setAttr("name", $name);
            }
            
            $this->submits[] = $subm;
        }
        
        public function getSubmit()
        {
            return !empty($this->submits[0])
                ? $this->submits[0] 
                : null;
        }
        
        public function getSubmits() 
        {
            return $this->submits;
        }
    }

?>