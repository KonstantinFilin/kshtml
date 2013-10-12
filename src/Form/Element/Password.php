<?php

    namespace KsHtml\Form\Element;

    /*
     * To change this template, choose Tools | Templates
     * and open the template in the editor.
     */

    /**
     * Description of PasswordElement
     *
     * @author ksf
     */
    class Password extends \KsHtml\Form\Element
    {
        function __construct($name, $label)
        {
            parent::__construct("input", $name, $label);
            $this->setAttrs(
                array(
                    "name" => $name,
                    "type" => "password",
                    "value" => ""
                )
            );

            $this->label = $label;
            $this->description = "";
            $this->error = "";
        }

        function setValue($value)
        {
            parent::setValue("");
        }
    }

?>
