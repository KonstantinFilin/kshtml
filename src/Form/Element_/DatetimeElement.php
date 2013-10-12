<?php

    /*
     * To change this template, choose Tools | Templates
     * and open the template in the editor.
     */

    /**
     * Description of DatetimeElement
     *
     * @author ksf
     */
    class HtmlForm_DatetimeElement extends HtmlForm_DateElement
    {
        protected $h;
        protected $min;
        protected $s;
        protected $delim2;
        protected $hideSec;
        protected $format;

        function __construct($name, $label)
        {
            parent::__construct($name, $label);

            $this->h = new HtmlForm_SelectElement($name . "[hour]", $label . " (hour)");
            $this->min = new HtmlForm_SelectElement($name . "[min]", $label . " (min)");
            $this->s = new HtmlForm_SelectElement($name . "[sec]", $label . " (sec)");
            $this->delim2 = ":";

            $this->setHours();
            $this->setMinutes();
            $this->setSeconds();

            $this->h->setEmptyValueLabel(null);
            $this->min->setEmptyValueLabel(null);
            $this->s->setEmptyValueLabel(null);
            $this->hideSec = false;
            $this->format = "Y-m-d H:i:s";
        }
        
        public function setFormat($format)
        {
            $this->format = $format;
        }

        public function setHours()
        {
            $vals = array();

            for($i=0; $i<=23; $i++) {
                $vals[$i] = $i;
            }

            $this->h->setAvailableValues($vals);
        }
        
        public function setHideSec($hideSec)
        {
            $this->hideSec = $hideSec;
        }

        public function setMinutes($short=true)
        {
            $vals = array();

            for($i=0; $i<=59; ) {
                $vals[$i] = $i;
                $i += $short ? 5 : 1;
            }

            $this->min->setAvailableValues($vals);
        }

        public function setSeconds($short=true)
        {
            $vals = array();

            for($i=0; $i<=59; ) {
                $vals[$i] = $i;
                $i += $short ? 5 : 1;
            }

            $this->s->setAvailableValues($vals);
        }

        public function setValue($value)
        {
            $d = DateTime::createFromFormat(
                //"Y-m-d H:i:s",
                $this->format,
                $value
            );

            if($d) {
                $this->d->setValue($d->format("d"));
                $this->mon->setValue($d->format("m"));
                $this->y->setValue($d->format("Y"));
                $this->h->setValue($d->format("H"));
                $this->min->setValue($d->format("i"));
                $this->s->setValue($d->format("s"));
                
/*d($d->format("i"));
d($this->min); die;*/
            }
        }

        public function validate()
        {
            $d = DateTime::createFromFormat(
                $this->format,
                $this->getPostedValue()
            );

            if(!$d) {
                $this->setError("Неверно указаны дата или время");
                return false;
            }

            return true;
        }

        public function getPostedValue()
        {
            $val1 = parent::getPostedValue();
            $val2 = !empty($_REQUEST[$this->elementName]) ? $_REQUEST[$this->elementName] : null;
            
            $h = $this->h->getPostedValue();
            
            if(strlen($h) < 2) {
                $h = "0" . $h;
            }
            
            $m = $this->min->getPostedValue();
            
            if(strlen($m) < 2) {
                $m = "0" . $m;
            }
            
            $val = $val1 . " " .
                $h . $this->delim2 .
                $m;
            
            if(!$this->hideSec) {
                $val .= $this->delim2 . $this->s->getPostedValue();
            }

            $d = DateTime::createFromFormat(
                $this->format,
                $val
            );
/*d($val);
d($this->format);
die;*/
            if($d) {
                return $d->format("Y-m-d H:i:s");
            }

            return null;
        }

        public function setDelim2($delim2)
        {
            $this->delim2 = $delim2;
        }

        public function getTag()
        {
            return parent::getTag() . " " .
                $this->h->getTag() . $this->delim2 .
                $this->min->getTag() . 
                (!$this->hideSec ? $this->delim2 . $this->s->getTag() : "");
        }
    }

?>
