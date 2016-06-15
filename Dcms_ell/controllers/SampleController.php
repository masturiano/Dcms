<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Dcms_SampleController extends USAP_Controller_Action {

    public function indexAction() {
        $request = $this->getRequest();
        $userReg = Zend_Registry::get('user');
        $acl = Zend_Registry::get('acl');
        $form = $this->getForm();

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $this->_helper->redirector('index', 'hello', 'default');
            }
        }
        $this->view->form = $form;
//        $acl->addResource('user');
//        $canEdit = $acl->isAllowed($userReg . '', 'user', 'user.edit') ? "allow edit" : "deny edit";
//        echo $canEdit;
//        echo $userReg;
        $toolset = $userReg->findToolsWithAdminRoles($userReg->getRoles());
        foreach($toolset as $context){
            if(!$acl->has($context)) {
                $acl->addResource($context);
            }
            if($acl->isAllowed($userReg . '', $context, 'data.edit')){
                echo $context."->allowed <br>";
            }else{
                echo $context."->not allowed <br>";
            }
        }
    }

    public function getForm() {
        return new Dcms_Form_SampleForm(array());
    }

}

?>
