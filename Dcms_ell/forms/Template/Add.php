<?php

class Dcms_Form_Template_Add extends USAP_Form
{

    public function init()
    {
        parent::init();

         $this->setName('Dcms_Template_Add');

        {
            $keyProperties = array(
                'decorators' => $this->getElementDecorators(),
                'label' => 'Template Name:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'value' => isset($this->_data['key']) ? $this->_data['key'] : "",
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
            );

            if (isset($this->_data['key'])) {
                $keyProperties['readonly'] = true;
            }

            $this->addElement('text', 'key', $keyProperties);

            $this->addElement('text', 'required',
                              array(
                'decorators' => $this->getElementDecorators(),
                'label' => '# Required:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => false,
                'value' => isset($this->_data['required']) ? $this->_data['required'] : "10",
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
                    )
            );

             $this->addElement('text', 'threshold',
                              array(
                'decorators' => $this->getElementDecorators(),
                'label' => '# Required:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => false,
                'value' => isset($this->_data['threshold']) ? $this->_data['threshold'] : "50",
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
                    )
            );

            $this->addHtml('<p>Select the sites that this spin will apply to, if it is not designed
                        for a specific site then just add the "Not site specific" option.</p>');

            $this->addServerSiteSelect('site', 'Select Site', false, false);

            $this->addDisplayGroup(
                array('key', 'site'), 'generalgroup',
                array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->getGroupDecorators(),
                'legend' => "Template item Details",

                    )
            );


            $this->addElement('text', 'required',
                              array(
                'decorators' => $this->getElementDecorators(),
                'label' => '# Required:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => false,
                'value' => isset($this->_data['required']) ? $this->_data['required'] : "10",
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
                    )
            );

             $this->addElement('text', 'threshold',
                              array(
                'decorators' => $this->getElementDecorators(),
                'label' => 'Simularity Threshold:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => false,
                'value' => isset($this->_data['threshold']) ? $this->_data['threshold'] : "50",
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
                    )
            );

            $this->addDisplayGroup(
                array('required', 'threshold'), 'qualitygroup',
                array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->getGroupDecorators(),
                'legend' => "Generation specifications",
                'description' => 'Detail the number of generated items
                        you require and the quality threshold, the threshold is a percentage
                        simularity with average text, a low value has low simularity.'

                    )
            );

        }


        {


            $this->addElement('text', 'partname',
                              array(
                'decorators' => $this->getElementDecorators(),
                'label' => 'Partname:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => false,
                'value' => isset($this->_data['partname']) ? $this->_data['partname'] : "",
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
                    )
            );

            $this->addElement('text', 'make',
                              array(
                'decorators' => $this->getElementDecorators(),
                'label' => 'Make:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => false,
                'value' => isset($this->_data['make']) ? $this->_data['make'] : "",
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
                    )
            );

            $this->addElement('text', 'model',
                              array(
                'decorators' => $this->getElementDecorators(),
                'label' => 'Model:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => false,
                'value' => isset($this->_data['model']) ? $this->_data['model'] : "",
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
                    )
            );

            $this->addElement('text', 'year',
                              array(
                'decorators' => $this->getElementDecorators(),
                'label' => 'Year:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => false,
                'value' => isset($this->_data['year']) ? $this->_data['year'] : "",
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
                    )
            );


            $this->addElement('text', 'brand',
                              array(
                'decorators' => $this->getElementDecorators(),
                'label' => 'Brand:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => false,
                'value' => isset($this->_data['brand']) ? $this->_data['brand'] : "",
                'attribs' => array(
                    'size' => 35,
                    'class' => 'uniform'
                )
                    )
            );


            $this->addElement('hidden', 'id',
                              array(
                'required' => false,
                'value' => isset($this->_data['id']) ? $this->_data['id'] : "",
                    )
            );

            $this->addElement('hidden', 'formname',
                              array(
                'required' => false,
                'value' => "add",
                    )
            );

            $this->addDisplayGroup(
                array('partname', 'make', 'model', 'year', 'brand', 'id', 'formname'), 'accessgroup',
                array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->getGroupDecorators(),
                'legend' => "Template item properties",
                'description' =>
                'Enter the proprties of this data item,. these properties will be bound to variables in the spintax'
                    )
            );


        }

        if (isset($this->_data['key'])) {
            $this->addSubmitButtons('Update');
        }

        else {
            $this->addSubmitButtons('Add');
        }


    }

}