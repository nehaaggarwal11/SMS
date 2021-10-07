<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
} 

class show_all_students extends Admin_Controller
{

    public $sch_setting_detail = array();

    public function __construct()
    {
        parent::__construct();
        $this->config->load('app-config');
        $this->load->library('smsgateway');
        $this->load->library('mailsmsconf');
        $this->load->library('encoding_lib');
        $this->load->model("classteacher_model");
        $this->load->model("timeline_model");
        $this->blood_group        = $this->config->item('bloodgroup');
        $this->sch_setting_detail = $this->setting_model->getSetting();
        $this->role;
    }

    public function show()
    {
        echo 'hi';
    }
}
    ?>