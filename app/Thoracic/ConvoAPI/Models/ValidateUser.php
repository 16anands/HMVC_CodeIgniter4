<?php namespace App\Thoracic\ConvoAPI\Models;

use CodeIgniter\Model;
use DateTime;
use DateTimeZone;


class ValidateUser extends Model{
    
    public function __construct(){
        $this->request = \Config\Services::request();
        $this->db      = \Config\Database::connect();
        $this->instance = new DateTime('now', new DateTimeZone(app_timezone()));
        $this->datetime = $this->instance->format('Y-m-d H:i:s');
    }
    
    public function matchHeading(){
        $builder = $this->db->table('users');
        $query = $builder->select('profile_id')
            ->where('trackerid',$this->request->getGet('header'))
            ->get();
        $id = $query->getResult();
        if(empty($id)){
            return false;
        }
        return $id[0]->profile_id;
    }

    public function updateHeading($profile_id){
        $builder = $this->db->table('users');
        $updatehead = array(
            'trackerid'=>$this->request->getGet('header'),
            'last_ip'=>$this->request->getIPAddress(),
            'app_version'=>'Convo v1.0',
            'update_datetime'=>$this->datetime,
            'lastlogin_date'=>$this->datetime
        );
        $builder->where('profile_id',$profile_id);
        $builder->update($updatehead);
        return $this->db->affectedRows();
    }
}