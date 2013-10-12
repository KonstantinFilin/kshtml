<?php

    class HtmlForm_CaptchaElement extends HtmlForm_Element
    {
        function __construct()
        {
            parent::__construct("img", "captcha", "Введите код с картинки");
            $this->url = "/captcha";
            $this->setAttr("onClick", "this.src='" . $this->url. "?r=' + Math.random()");
        }

        public function getTag()
        {
            $this->setAttr("src", $this->url);
            $this->setAttr("alt", "");

            $tag = new HtmlTag("input");
            $tag->setAttr("type", "text");
            $tag->setAttr("name", "captcha");
            $tag->setAttr("size", "5");

            return parent::getTag() . "<br />" . $tag->__toString();
        }

        public function fillFromArray($arr, $filename = null)
        {
            parent::fillFromArray($arr, $filename);

            if(!empty($arr["url"])) {
                $this->url = $arr["url"];
            }

            if(!empty($arr["label"])) {
                $this->setLabel($arr["label"]);
            }
        }

        public function getPostedValue()
        {
            $val = parent::getPostedValue();
            $val = preg_replace("|[^A-Za-z0-9]|", "", $val);

            return $val;
        }

    }

?>