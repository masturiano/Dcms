<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_Form_Index_Decorator extends Zend_Form {

    public function init() {
        $countryList = array('USA', 'UK');
        $firstName = $this->createElement('text', 'firstName');
        $firstName->setLabel('First Name')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 50))
                ->setValue('Gjenever')
                ->setRequired(true);

        $lastName = $this->createElement('text', 'lastName');
        $lastName->setLabel('Last Name:')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 50))
                ->setValue('')
                ->setRequired(true);

        $address1 = $this->createElement('text', 'address1');
        $address1->setLabel('Address1:')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 50))
                ->setValue('')
                ->setRequired(true);

        $address2 = $this->createElement('text', 'address2');
        $address2->setLabel('Address2:')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 50))
                ->setValue('')
                ->setRequired(false);

        $postalCode = $this->createElement('text', 'postalCode');
        $postalCode->setLabel('Postalcode:')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 15))
                ->setValue('')
                ->setRequired(false);

        $city = $this->createElement('text', 'city');
        $city->setLabel('City:')
                ->setAttrib('size', 25)
                ->setValue('')
                ->setRequired(false)
                ->setAttrib('tabindex', '6');

        $state = $this->createElement('text', 'state');
        $state->setLabel('State:')
                ->setAttrib('size', 6)
                ->setAttrib('maxlength', 2)
                ->setValue('')
                ->setRequired(false)
                ->setAttrib('tabindex', '7');

        $country = $this->createElement('select', 'country');
        $country->setLabel('Country:')
                ->setAttrib('class', 'select')
                ->addMultiOptions($countryList)
                ->setRequired(false);

        $phone = $this->createElement('text', 'phone');
        $phone->setLabel('Phone:')
                ->setAttrib('size', 25)
                ->setAttrib('maxlength', '25')
                ->setValue('')
                ->setRequired(true);

        $emailAddress = $this->createElement('text', 'emailAddress');
        $emailAddress->setLabel('Email:')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(5, 50))
                ->addValidator('EmailAddress')
                ->setValue('')
                ->setRequired(true);

        $website = $this->CreateElement('text', 'website');
        $website->setLabel("Website:")
                ->setAttrib('size', 25);

        $userName = $this->createElement('text', 'userName');
        $userName->setLabel('Username:')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(5, 50))
                ->setValue('')
                ->setRequired(true);

        $password = $this->createElement('password', 'password');
        $password->setLabel('Password:')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 50))
                ->setRequired(true)
                ->setValue('')
                ->setIgnore(false);

        $confirmPassword = $this->createElement('password', 'confirmPassword');
        $confirmPassword->setLabel('Confirm Password:')
                ->setAttrib('size', 25)
                ->addValidator('StringLength', false, array(3, 50))
                ->setRequired(true)
                ->setValue('')
                ->setIgnore(false);

        $this->addElements(array(
            $firstName,
            $lastName,
            $address1,
            $address2,
            $postalCode,
            $city,
            $state,
            $country,
            $phone,
            $emailAddress,
            $website,
            $userName,
            $password,
            $confirmPassword
                )
        );

        $this->addDisplayGroup(array(
            'firstName',
            'lastName',
            'userName',
            'address1',
            'address2',
            'postalCode',
            'city',
            'state',
            'country',
            'phone'
                ), 'contact', array('legend' => 'Contact Information'));

        $contact = $this->getDisplayGroup('contact');
        $contact->setDecorators(array(
            'FormElements',
            'Fieldset',
            array('HtmlTag', array('tag' => 'div', 'style' => 'width:50%;;float:left;'))
        ));

        $this->addDisplayGroup(array(
            'password',
            'confirmPassword',
                ), 'pass', array('legend' => 'Password'));

        $pass = $this->getDisplayGroup('pass');
        $pass->setDecorators(array(
            'FormElements',
            'Fieldset',
            array('HtmlTag', array('tag' => 'div', 'openOnly' => true, 'style' => 'width:48%;;float:right'))
        ));

        $this->addDisplayGroup(array(
            'emailAddress',
            'website',
                ), 'web', array('legend' => 'Web Information'));

        $web = $this->getDisplayGroup('web');
        $web->setDecorators(array(
            'FormElements',
            'Fieldset',
            array('HtmlTag', array('tag' => 'div', 'closeOnly' => true))
        ));

        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'div', 'style' => 'width:98%')),
            'Form'
        ));
    }

}

?>
