<?php

    namespace KsHtml\Form\Element;
    /*
     * To change this template, choose Tools | Templates
     * and open the template in the editor.
     */

    /**
     * Description of FileElement
     *
     * @author ksf
     */
    class File extends \KsHtml\Form\Element\String
    {
        function __construct($name, $label)
        {
            parent::__construct($name, $label);

            $this->setAttrs(
                array(
                    "type" => "file"
                )
            );
        }
    }

?>
