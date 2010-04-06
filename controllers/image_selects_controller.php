<?php
/**
 * Controller that is used to display multiselect image control.
 * It should be used as a parent class in a control with data.
 */
class ImageSelectsController extends AppController 
{
	var $name = 'ImageSelects';
	var $helpers = array('Html', 'Form', 'Ajax', 'Javascript', 'Ajaxupload');
    var $components = array('RequestHandler');
    var $paginate = array('limit' => 5);
    var $model_instance = null;

    /*
     * Parent class should call this method with the name of the model.
     * Model is used to retrieve the data.
     */
    function set_instance($modelKey)
    {
        if ($this->model_instance == null)
        {
            if (App::import('Model', $modelKey))
            {
                $this->model_instance = new $modelKey;
            }
        }
    }
    
    /*
     * Can be used through AJAX call to add the main functionality
     */
    function preview()
    {
        $this->model_instance->recursive = 0;
        
        // get the id of selected items:
        if (!empty($this->params['form']['selected']))
        {
            $selected = $this->params['form']['selected'];
            $selected_ids = array('id' => $selected);
            $conditions = array("NOT" => $selected_ids);
            $this->set('allselected', $this->model_instance->find('all', array('conditions' => $selected_ids)));
        }
        else
        {
            $conditions = array();
            $this->set('allselected', array());
        }
        $data = $this->paginate($conditions);
        $this->set('allphotos', $data);
        
        $this->set('modelClass', $this->modelClass);
        
        // Point that we are using plugin and should use plugin's .ctp for rendering
        $this->plugin = 'image_select';
        $this->render(false, null, '/image_selects/preview');
    }
}