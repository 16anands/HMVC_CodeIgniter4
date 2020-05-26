<?php namespace App\Thoracic\HumanChat\Models;

use CodeIgniter\Model;

class ValidateUser extends Model{
    
    public function __construct(){
        $this->request = \Config\Services::request();
        $this->Heading = $this->request->getGet('header');
        $this->db      = \Config\Database::connect();
    }
    
    public function matchHeading(){
        $builder = $this->db->table('users');
        $query = $builder->select('profile_id')
            ->where('trackerid',$this->Heading)
            ->get();
        $id = $query->getResult();
        if(empty($id)){
            return false;
        }
        return $id[0]->profile_id;
    }
}