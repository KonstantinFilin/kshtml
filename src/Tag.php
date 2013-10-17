<?php

namespace KsHtml;

/**
 * Opened or closed html tag
 */
class Tag {

    /**
     *
     * @var string The name of the tag. For example, form for <form> tag or
     * video for <video> tag
     */
    protected $name;

    /**
     *
     * @var array List of css classes
     */
    protected $classes;

    /**
     *
     * @var array List of tag attributes in form of name=>value
     */
    protected $attrs;

    /**
     *
     * @var string Tag content. For example, label "link" in tag
     * <a href="http://www.site.com">link</a>
     */
    protected $content;

    /**
     *
     * @var boolean True, if tag has closing tag:
     * <a>, <form>, <div>, etc. False, if there are no closing tag: <img />,
     * <br />, <hr /> etc.)
     */
    protected $hasClosingTag;

    /**
     *
     * @var boolean If true, then html special chars in content of tag will be
     * replaced for its html entities during tag renering. Default is true.
     */
    protected $encodeContent;

    /**
     *
     * @param string $name Name of the tag
     * @param boolean $hasClosingTag If it needs closing tag. Default to false
     */
    function __construct($name, $hasClosingTag=false) {
        $this->name = $name;
        $this->hasClosingTag = $hasClosingTag;
        $this->attrs = array();
        $this->content = "";
        $this->encodeContent = true;
    }

    /**
     * Adds a css class name to the class attribute
     * @param string $cls New css class name
     * @return \KsHtml\Tag Current tag
     */
    public function addClass($cls) {
        if($this->classes && in_array($cls, $this->classes)) {
            return $this;
        }

        $this->classes[] = $cls;
        return $this;
    }

    /**
     * Removes a css class name to the class attribute
     * @param string $cls Css class name to remove
     * @return \KsHtml\Tag Current tag
     */
    public function removeClass($cls) {
        $key = array_search($cls, $this->classes);

        if($key !== false) {
            unset($this->classes[$key]);
        }

        return $this;
    }

    /**
     * Disable replacement of special chars to its html entities. Needed
     * when child tags used in content or when was made earlier
     * @return \KsHtml\Tag
     */
    public function disableEncodeContent() {
        $this->encodeContent = false;
        return $this;
    }

    /**
     *
     * @return \KsHtml\Tag
     */
    public function disableValueEncoding()
    {
        return $this->disableEncodeContent();
    }

    /**
     * Sets attribute to the tag. If attribute exists its value
     * will be overriden. If value is null, then only attribute's
     * name will be printed ("readonly" instead of 'readonly="true"'
     * @param String $name Attribute's name, for example: "src"
     * @param String $value Attribute's value, for example:
     * "http://www.google.com/". Default value is null
     * @return \KsHtml\Tag
     */
    public function setAttr($name, $value=null) {
        $this->attrs[$name] = $value;
        return $this;
    }

    /**
     * Returns chosen attribute's value
     * @param string $name Attribute's name
     * @return string|null Attribute's value or null if there are no
     * such an attribute
     */
    public function getAttr($name) {
        return isset($this->attrs[$name]) ? $this->attrs[$name] : null;
    }

    /**
     * Removes attribute from the tag
     * @param string $name Attribute's name
     */
    public function delAttr($name) {
        if (isset($this->attrs[$name])) {
            unset($this->attrs[$name]);
        }
    }

    /**
     * Set attributes list.
     * @param Array $attrs Attributes list in form of name=>value.
     * @return \KsHtml\Tag
     */
    public function setAttrs($attrs) {
        if ($attrs) {
            foreach ($attrs as $name => $value) {
                $this->setAttr($name, $value);
            }
        }

        return $this;
    }

    /**
     * Sets attributes content. Previous content will be overriden
     * @param String $content Attribute's content
     * @return \KsHtml\Tag
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    /**
     * Add content to the current content
     * @param String $content Content to add
     * @return \KsHtml\Tag
     */
    public function addContent($content) {
        $this->content .= $content;
        return $this;
    }

    /**
     * Renders child tag and adds resulting string to existing content
     * @param \KsHtml\Tag $tag Child tag
     * @return \KsHtml\Tag
     */
    public function addChildTag(\KsHtml\Tag $tag) {
        $this->content .= $tag->__toString();
        return $this;
    }

    /**
     * Renders and returns a leading space and attributes string in form of
     * [ attr1name="attr1value" attr2name="attr2value" [...]]. Attribute values
     * are escaped
     * @return string Resulting string
     */
    private function getAttributesString() {
        $ret = "";

        if($this->classes) {
            $this->setAttr("class", implode(" ", $this->classes));
        }

        if ($this->attrs) {
            foreach ($this->attrs as $name => $value) {
                $ret .= " " . (($value !== null) ? $name . "=\"" . htmlspecialchars($value) . "\"" : $name);
            }
        }

        return $ret;
    }

    /**
     * Returns tag's open part with attributes.
     * @return string Tag's open part with attributes
     */
    public function getOpenTag() {
        $ret = "<" . $this->name . $this->getAttributesString();

        if (!$this->hasClosingTag) {
            $ret .= " /";
        }

        return $ret . ">";
    }

    /**
     * Returns tag's content
     * @return string Tag's content, escaped or not depending of whether
     * disableEncodeContent() method was called
     */
    public function getContent() {
        if (!$this->content) {
            return "";
        }

        if ($this->encodeContent) {
            return htmlspecialchars($this->content);
        } else {
            return $this->content;
        }
    }

    /**
     * Returns tag's closing part or empty string if there are no closing part
     * @return string Tag's closing part or empty string if there are no closing part
     */
    public function getClosingTag() {
        return $this->hasClosingTag ? "</" . $this->name . ">" : "";
    }

    /**
     * Renders the tag
     * @return string Rendered tag
     */
    public function __toString() {
        if ($this->hasClosingTag) {
            return $this->getOpenTag() .
            $this->getContent() .
            $this->getClosingTag();
        } else {
            return $this->getOpenTag();
        }
    }
}

?>
