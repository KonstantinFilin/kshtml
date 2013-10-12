<?php

namespace KsHtml\Form;

/**
 * Description of HtmlForm_Handler
 *
 * @author kostya
 */
class Handler 
{
    /**
     *
     * @var \KsHtml\Form
     */
    protected $form;
    
    protected $filterList;
    protected $elementFilterList;
    protected $elementValidatorList;
    protected $postedData;
            
    function __construct(\KsHtml\Form $form) {
        $this->form = $form;
        $this->filterList = array();
        $this->postedData = array();
        $this->elementValidatorList = array();
        $this->elementFilterList = array();
    }
    
    function isSubmitted()
    {
        return !empty($_POST);
    }
    
    function setElementListValidator(
        \KsUtils\Validator $validator, 
        $ellist=array()
    )
    {
        if(!$ellist) {
            $ellist = $this->form->getElementNames();
        }
        
        foreach($ellist as $elName) {
            $this->setElementValidator($elName, $validator);
        }
    }
    
    function setElementValidator($elName, \KsUtils\Validator $validator)
    {
        $this->elementValidatorList[$elName][] = $validator;
    }
    
    function addFilter(\KsUtils\StrFilter $filter)
    {
        $this->filterList[] = $filter;
    }
    
    function addElementFilter($elementName, \KsUtils\StrFilter $filter)
    {
        $this->elementFilterList[$elementName][] = $filter;
    }
            
    function preProcess()
    {
    }
    
    function process()
    {
        $postedData = $this->getPostedData();

        echo "<pre>";
        var_dump($_POST);
        var_dump($postedData);
        die;        
    }
 
    function checkAndProcess()
    {
        if($this->isSubmitted()) {
            if($this->validate()) {
                $this->preProcess();
                $processRes = $this->process();
                $this->postProcess();
                
                return $processRes;
            } else {
               // echo "<pre>";
               // var_dump($this->form->getElementErrors());
               // die;
               // die("validate error");
                $this->form->setElementValues($this->getPostedData());
                /*echo "<pre>";
                var_dump($this->getPostedData());
                var_dump($this->form->getElements());
                die;*/
            }
        }        
        
        return false;
    }
    
    function validate()
    {
        $ret = true;
        $postedData = $this->getPostedData();
        
        foreach($this->elementValidatorList as $elName => $vlist) {
            foreach($vlist as $validator) {
                if($validator instanceof \KsUtils\Validator) {
                    $value = isset($postedData[$elName])
                            ? $postedData[$elName] 
                            : null;
                    // echo "Element: " . $elName . ", validator: " . get_class($validator) . "<br />";
                    if(!$validator->check($value)) {
                        $error = $validator->getErrorMessage($value);
                        $this->form->addElementError(
                            $elName, 
                            $error
                        );
                        $ret = false;
                        break 1;
//                    } else {
//                        echo "element " . $elName . "is OK (type: " . get_class($validator) . ")<br />";
                    }
                }
            }
        }
        
        if(!$ret) {
            $this->form->setElementValues($postedData);
        }
        
        return $ret;
    }
    
    function postProcess()
    {
        
    }
    
    function getPostedData()
    {
        if($this->postedData) {
            return $this->postedData;
        }
        
        foreach($this->form->getElements() as $elName => $elObj) {
            if(!$elObj instanceof \KsHtml\Form\Element) {
                continue;
            }
            
            $val = $elObj->getPostedValue();

            if($this->filterList) {
                foreach($this->filterList as $filter) {
                    if($filter instanceof \KsUtils\StrFilter) {
                        $val = $filter->filter($val);
                    }
                }
            }
            
            if(!empty($this->elementFilterList[$elName])) {
                foreach($this->elementFilterList[$elName] as $filter) {
                    if($filter instanceof \KsUtils\StrFilter) {
                        $val = $filter->filter($val);
                    }
                }
            }
            
            $this->postedData[$elName] = $val;
        }

        return $this->postedData;
    }

}

?>
