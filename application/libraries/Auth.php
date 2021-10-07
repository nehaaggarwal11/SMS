<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Auth
{

    public $CI;
    protected $errors;
    protected $messages;

    //this is the expiration for a non-remember session
    //var $session_expire    = 600;

    public function __construct()
    {
        $this->CI       = &get_instance();
        $this->messages = array();
        $this->errors   = array();

        $this->set_timezone();
        $this->CI->load->database();
        //$this->CI->load->library('encrypt'); //for php 7.2+ support
    }

    /*
    this checks to see if the admin is logged in
    we can provide a link to redirect to, and for the login page, we have $default_redirect,
    this way we can check if they are already logged in, but we won't get stuck in an infinite loop if it returns false.
     */

    public function logged_in()
    {
        return (bool) $this->CI->session->userdata('admin');
    }

    public function user_logged_in()
    {
        return (bool) $this->CI->session->userdata('student');
    }

    public function user_redirect()
    {
        if ($this->CI->session->has_userdata('student')) {
            $user = $this->CI->session->userdata('student');
            $role = $user['role'];
            if ($role == "student") {
                redirect('user/user/dashboard');
            } else if ($role == "parent") {
                redirect('parent/parents/dashboard');
            } else if ($role == "teacher") {
                redirect('teacher/teacher/dashboard');
            } else if ($role == "accountant") {
                redirect('accountant/accountant/dashboard');
            } else if ($role == "librarian") {
                redirect('librarian/librarian/dashboard');
            } else {
                redirect('site/userlogin');
            }
        } else {
            redirect('site/userlogin');
        }
    }

    public function is_logged_in($default_redirect = false)
    {
        $admin = $this->CI->session->userdata('admin');

        if (!$admin) {

            $_SESSION['redirect_to'] = current_url();
            redirect('site/login');

            return false;
        } else {

            if ($default_redirect) {

                redirect('admin/admin/dashboard');
            }
            return true;
        }
    }

    public function is_logged_in_user($role = false)
    {

        if ($this->CI->session->has_userdata('student')) {
            $user = $this->CI->session->userdata('student');
            if (!$this->CI->session->has_userdata('current_class')) {
                if ($this->CI->router->fetch_method() != "choose") {
                    redirect('user/user/choose');
                }

            }

            if (!$role) {
                redirect('site/userlogin');
            }
            //  else {
            //     if ($user['role'] == $role) {
            //         return true;
            //     } else {
            //         redirect($user['role'] . '/unauthorized');
            //     }
            // }
        } else {
            $_SESSION['redirect_to_user'] = current_url();
            redirect('site/userlogin');
        }
    }

    /*
    this function does the logging in.
     */

    /*
    this function does the logging out
     */

    public function logout()
    {
        $this->CI->session->unset_userdata('admin');
        $this->CI->session->sess_destroy();
    }

    public function set_timezone()
    {

        if ($this->CI->customlib->getTimeZone()) {
            date_default_timezone_set($this->CI->customlib->getTimeZone());
        } else {
            return date_default_timezone_set('UTC');
        }
    }

    /*
    This function resets the admins password and emails them a copy
     */

    public function reset_password($email)
    {
        $admin = $this->get_admin_by_email($email);
        if ($admin) {
            $this->CI->load->helper('string');
            $this->CI->load->library('email');

            $new_password      = random_string('alnum', 8);
            $admin['password'] = sha1($new_password);
            $this->save_admin($admin);

            $this->CI->email->from($this->CI->config->item('email'), $this->CI->config->item('site_name'));
            $this->CI->email->to($email);
            $this->CI->email->subject($this->CI->config->item('site_name') . ': Admin Password Reset');
            $this->CI->email->message('Your password has been reset to ' . $new_password . '.');
            $this->CI->email->send();
            return true;
        } else {
            return false;
        }
    }

    /*
    This function gets the admin by their email address and returns the values in an array
    it is not intended to be called outside this class
     */

    private function get_admin_by_email($email)
    {
        $this->CI->db->select('*');
        $this->CI->db->where('email', $email);
        $this->CI->db->limit(1);
        $result = $this->CI->db->get('admin');
        $result = $result->row_array();

        if (sizeof($result) > 0) {
            return $result;
        } else {
            return false;
        }
    }

    /*
    This function takes admin array and inserts/updates it to the database
     */

    public function save($admin)
    {
        if ($admin['id']) {
            $this->CI->db->where('id', $admin['id']);
            $this->CI->db->update('admin', $admin);
        } else {
            $this->CI->db->insert('admin', $admin);
        }
    }

    /*
    This function gets a complete list of all admin
     */

    public function get_admin_list()
    {
        $this->CI->db->select('*');
        $this->CI->db->order_by('lastname', 'ASC');
        $this->CI->db->order_by('firstname', 'ASC');
        $this->CI->db->order_by('email', 'ASC');
        $result = $this->CI->db->get('admin');
        $result = $result->result();

        return $result;
    }

    /*
    This function gets an individual admin
     */

    public function get_admin($id)
    {
        $this->CI->db->select('*');
        $this->CI->db->where('id', $id);
        $result = $this->CI->db->get('admin');
        $result = $result->row();

        return $result;
    }

    public function check_id($str)
    {
        $this->CI->db->select('id');
        $this->CI->db->from('admin');
        $this->CI->db->where('id', $str);
        $count = $this->CI->db->count_all_results();

        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function check_email($str, $id = false)
    {
        $this->CI->db->select('email');
        $this->CI->db->from('admin');
        $this->CI->db->where('email', $str);
        if ($id) {
            $this->CI->db->where('id !=', $id);
        }
        $count = $this->CI->db->count_all_results();

        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function delete($id)
    {
        if ($this->check_id($id)) {
            $admin = $this->get_admin($id);
            $this->CI->db->where('id', $id);
            $this->CI->db->limit(1);
            $this->CI->db->delete('admin');

            return $admin->firstname . ' ' . $admin->lastname . ' has been removed.';
        } else {
            return 'The admin could not be found.';
        }
    }

    public function validate_child($id = null)
    {
        $parent         = $this->CI->session->userdata('student');
        $parent_id      = $parent['id'];
        $students_array = $this->CI->student_model->read_siblings_students($parent_id);
        if ($id) {
            foreach ($students_array as $stu_key => $stu_value) {
                if ($stu_value->id == $id) {
                    return true;
                }
            }
            redirect('parent/unauthorized');
        }
    }


    public function import_dump($fd_name)
    {
       
        $filename = './temp/' . $fd_name . '/db_import.sql';
        if (file_exists($filename)) {
            $progressFilename = $filename . '_filepointer'; 
            $errorFilename    = $filename . '_error'; 
            $fp = fopen($filename, 'r');
            if (!$fp) {
                $this->set_error('Update error! There is some issue occurred during update, please contact to support.');
                return false;
            }
            $queryCount = 0;
            $query      = '';

            $db_debug = $this->CI->db->db_debug; 
            $this->CI->db->db_debug = false;
            while ($line = fgets($fp, 1024000)) {
                if (substr($line, 0, 2) == '--' or trim($line) == '') {
                    continue;
                }

                $query .= $line;

                if (substr(trim($query), -1) == ';') {

                    if (!$this->CI->db->query($query)) {
                        $db_error = $this->CI->db->error();
                        $this->set_error($db_error['message']);
                        return false;
                    }
                    $query = '';

                    $queryCount++;
                }

            }
            $this->CI->db->db_debug = $db_debug; 
            if (feof($fp)) {
                return true;
            } else {
                $this->set_error('Update error! There is some issue occurred during update, please contact to support.');
                return false;
            }
        }
        $this->set_error('Update error! There is some issue occurred during update, please contact to support.');
        return false;

    }

    public function set_error($error)
    {
        $this->errors[] = $error;

        return $error;
    }

    public function set_message($message)
    {
        $this->messages[] = $message;

        return $message;
    }

    public function clear_messages()
    {
        $this->messages = array();

        return true;
    }

    public function clear_error()
    {
        $this->errors = array();

        return true;
    }
    public function messages()
    {
        return $this->messages;
    }

    public function error()
    {
        return $this->errors;

    }

    public function recurse_copy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function deleteDir($dirPath)
    {

        if (!is_dir($dirPath)) {
            return false;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }


    public function filename($filename)
    {
        return preg_replace('/.[^.]*$/', '', $filename);
    }

}
