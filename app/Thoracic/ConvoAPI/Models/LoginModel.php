<?php namespace App\Thoracic\ConvoAPI\Models;

use CodeIgniter\Model;
use DateTime;
use DateTimeZone;
use \App\Libraries\GlobalIdentity;

class LoginModel extends Model {
    
    public function __construct(){
        $this->genGUID = GlobalIdentity::genGUID();
        $this->instance = new DateTime('now', new DateTimeZone(app_timezone()));
        $this->datetime = $this->instance->format('Y-m-d H:i:s');
        $this->date = $this->instance->format('Y-m-d');
        $this->db = \Config\Database::connect();
        $this->encrypter = \Config\Services::encrypter();
    }
    
    private function fetchProfile($id){
        $builder = $this->db->table('users');
        $query = $builder->select('profile_id,status')
            ->where('email',$id)
            ->where('role_id',8)
            ->get();
        $user = $query->getResult();
        return $user[0];
    }

    private function fetchPassword($id){
        $builder = $this->db->table('users');
        $query = $builder->select('password_hash')
            ->where('profile_id',$id)
            ->get();
        $password = $query->getResult();
        return hex2bin($password[0]->password_hash);
    }

    private function savePassword($id,$hash){
        $builder = $this->db->table('users');
        $updatehash = array(
            'trackerid'=>'',
            'last_ip'=>$this->request->getIPAddress(),
            'password_hash'=>$hash,
            'update_datetime'=>$this->datetime
        );
        $builder->where('profile_id',$id);
        $builder->update($updatehash);
        return $this->db->affectedRows();
    }

    private function fetchKey($id){
        $builder = $this->db->table('key');
        $query = $builder->select('salt')
            ->where('profile_id',$id)
            ->get();
        $key = $query->getResult();
        return hex2bin($key[0]->salt);
    }

    private function saveKey($id,$key){
        $builder = $this->db->table('key');
        $updatekey = array(
            'salt'=>$key
        );
        $builder->where('profile_id',$id);
        $builder->update($updatekey);
        return $this->db->affectedRows();
    }

    public function fetchUser($id){
//         $encoded = bin2hex(Encryption::createKey(32));
//         echo $encoded;
//         //echo "<br>";
//         $key = hex2bin($encoded);
//        // $key = Encryption::createKey(32);
//        // echo $key;
//         $plainText = 'ANAND';
// echo "\n";
//         $ciphertext = bin2hex($this->encrypter->encrypt($plainText,$key));
//         echo $ciphertext;
//         // Outputs: This is a plain-text message!
//        // echo $this->encrypter->decrypt($ciphertext,$key);
//         exit;
        $email = strtolower(esc($id->userid));
        $user = $this->fetchProfile($email);
        if(empty($user)){
            return 2;
        }
        if($user->status==0){
            return 3;
        }
        $hash = $this->fetchPassword($user->profile_id);
        $key = $this->fetchKey($user->profile_id);
        if($id->password==$this->encrypter->decrypt($hash,$key)){
            return $user->profile_id;
        }
        return 4;
    }
    
    public function saveNewPassword($id){
        $hash = $this->fetchPassword($id->profile_id);
        $key = $this->fetchKey($id->profile_id);
        if($id->oldpassword==$this->encrypter->decrypt($hash,$key)){
            $newbinkey = Encryption::createKey(32);
            $newhash = bin2hex($this->encrypter->encrypt($id->newpassword,$newbinkey));
            $this->savePassword($id->profile_id,$newhash);
            $this->saveKey($id->profile_id,bin2hex($newbinkey));
            return $user->profile_id;
        }
        return false;
    }
        
}