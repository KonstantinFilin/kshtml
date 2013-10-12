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
    class HtmlForm_DayElement extends HtmlForm_SelectElement
    {
        function __construct($name, $label)
        {
            parent::__construct($name, $label);
            $this->setRange();
            $this->emptyValueLabel = null;
        }

        protected function setRange()
        {
            $vals = array();

            for($i=1; $i<=31; $i++) {
                $vals[$i] = $i;
            }

            $this->setAvailableValues($vals);
        }
    }

?>
