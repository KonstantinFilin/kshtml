<?php

namespace KsHtml\Form\Renderer;

/**
 * Description of HtmlForm_ElementStandartRenderer
 *
 * @author kostya
 */
class Twig implements \KsHtml\Form\RendererIf
{
    /**
     *
     * @var Twig_Environment
     */
    protected $twig;
    
    /**
     *
     * @var HtmlForm
     */
    protected $form;
    
    protected $tpl;
    protected $elementTemplate;
    
    function __construct(\Twig_Environment $twig, \KsHtml\Form $form) 
    {
        $this->twig = $twig;
        $this->form = $form;
        $this->setDefaultTemplates();
    }
    
    function setTemplate($tpl)
    {
        $this->tpl = $tpl;
    }
    
    function setElementTemplate($elementName, $tpl)
    {
        $this->elementTemplate[$elementName] = $tpl;
    }
    
    public function setDefaultTemplates()
    {
        $map = array(
            "KsHtml\Form\Element\String" => "string",
            "KsHtml\Form\Element\Int" => "string",
            "KsHtml\Form\Element\Text" => "text"
        );
        
        foreach($this->form->getElements() as $elObj) {
            $type = get_class($elObj);
            $template = "form/elements/" . $map[$type] . ".html.twig";
            $this->setElementTemplate($elObj->getName(), $template);
        }
    }
    
    public function get()
    {
        return $this->twig->render(
            $this->tpl,
            array(
                "form" => $this->form,
                "elementTemplate" => $this->elementTemplate
            )
        );
    }
}

?>
