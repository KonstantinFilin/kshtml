<?php

    /*
     * To change this template, choose Tools | Templates
     * and open the template in the editor.
     */

    /**
     * Description of YearElement
     *
     * @author ksf
     */
    class HtmlForm_YearElement extends HtmlForm_SelectElement
    {
        protected $minYear;
        protected $maxYear;

        function __construct($name, $label)
        {
            parent::__construct($name, $label);

            $this->minYear = date("Y")-20;
            $this->maxYear = date("Y")+20;

            $this->setYearRange($this->minYear, $this->maxYear);
            $this->emptyValueLabel = null;
        }

        protected function setYearRange($minYear, $maxYear)
        {
            if($minYear > $maxYear) {
                list($minYear, $maxYear) = array($maxYear, $minYear);
            }

            $years = array();

            for($i=$minYear; $i<=$maxYear; $i++) {
                $years[$i] = $i;
            }

            $this->setAvailableValues($years);
        }
    }

?>
