<?php

    namespace KsHtml\Form\Element;

    /*
     * To change this template, choose Tools | Templates
     * and open the template in the editor.
     */

    /**
     * Description of DateElement
     *
     * @author ksf
     */
    class Date  extends KsHtml\Form\Element
    {
        protected $d;
        protected $mon;
        protected $y;
        protected $delim;

        function __construct($name, $label)
        {
            parent::__construct(null, $name, $label);

            $this->d = new HtmlForm_DayElement($name . "[day]", $label . " (day)");
            $this->mon = new HtmlForm_MonthElement($name . "[month]", $label . " (month)");
            $this->y = new HtmlForm_YearElement($name . "[year]", $label . " (year)");
            $this->delim = ".";
        }

        public function setValue($value)
        {
            /*$d = DateTime::createFromFormat(
                "Y-m-d",
                $value
            );*/

            $d = new \DateTime($value);

            if($d) {
                $this->d->setValue($d->format("d"));
                $this->mon->setValue($d->format("m"));
                $this->y->setValue($d->format("Y"));
            }
        }

        public function getPostedValue()
        {
            $val = parent::getPostedValue();

            if(!$val) {
                return null;
            }

            $d = new DateTime($this->y->getPostedValue() . "-" . $this->mon->getPostedValue() . "-" . $this->d->getPostedValue());

            if($d) {
                return $d->format('Y-m-d');
            }

            return null;
        }

        public function getDay()
        {
            return $this->d;
        }

        public function getMonth()
        {
            return $this->mon;
        }

        public function getYear()
        {
            return $this->y;
        }

        public function getTag()
        {
            return $this->d->getTag() . $this->delim . $this->mon->getTag() . $this->delim . $this->y->getTag();
        }
    }

?>
