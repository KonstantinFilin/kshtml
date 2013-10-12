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
    class HtmlForm_MonthElement extends HtmlForm_SelectElement
    {
        protected $minYear;
        protected $maxYear;

        function __construct($name, $label)
        {
            parent::__construct($name, $label);

            $monthes = array(
                1 => "Января",
                2 => "Февраля",
                3 => "Марта",
                4 => "Апреля",
                5 => "Мая",
                6 => "Июня",
                7 => "Июля",
                8 => "Августа",
                9 => "Сентабря",
                10 => "Октября",
                11 => "Ноября",
                12 => "Декабря"
            );

            $this->setAvailableValues($monthes);
            $this->emptyValueLabel = null;
        }
    }

?>
