<?php namespace App\Thoracic\ConvoAPI\Controllers;

use App\Thoracic\ConvoAPI\Models\ValidateUser;
use App\Thoracic\ConvoAPI\Models\LoginModel;

class Login extends BaseController{
    
    public function __construct(){
        $this->ValidateUser = new ValidateUser();
        $this->LoginModel = new LoginModel();
    }
	
    private function inputhandle($in,$count=0,$valid=0){
        $in = json_decode($in,true);  
        if(empty($in)){
            $response['error']  = 'E001';
            $response['message'] = 'No Input Received';
            echo json_encode($response);
            exit;
        }
        if(count($in)!=$count && $count!=0){
            $response['error']  = 'E008';
            $response['message'] = 'Input Count Mismatch';
            echo json_encode($response);
            exit;
        }
        if($valid!=0){
            if(!isset($in['profile_id'])){
                //device timestamp
                $response['error']  = 'E002';
                $response['message'] = 'No Profile Received';
                echo json_encode($response);
                exit;
            }
            $id = $this->ValidateUser->matchHeading();
            if($id!=$in['profile_id']){
                $response['error']  = 'E003';
                $response['message'] = 'Authentication Failure';
                echo json_encode($response);
                exit;
            }
        }
    }
    
    public function getLoginMe(){
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),2);
        $profile_id=$this->LoginModel->fetchUser($inward); 
        if($profile_id){
            $head=$this->ValidateUser->updateHeading($profile_id); 
            $response['success']  = 'KC';
            $response['message'] = 'Login Success';
            $response['payload'] = $profile_id;
            echo json_encode($response);
            exit;
        }
        else{
            if($configs==2){
                $response['success']  = 'KC';
                $response['message'] = 'User Not Found';
                echo json_encode($response);
                exit;
            }
            if($configs==3){
                $response['success']  = 'KC';
                $response['message'] = 'User Account Suspended';
                echo json_encode($response);
                exit;
            }
            if($configs==4){
                $response['success']  = 'KC';
                $response['message'] = 'User Password Mismatch';
                echo json_encode($response);
                exit;
            }
        }
    }
    
    public function setChangePassword(){ //KAPI01
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),3,1);
        $change=$this->LoginModel->saveNewPassword($inward); 
        if($change){
            $response['success']  = 'KC';
            $response['message'] = 'Password Changed';
            echo json_encode($response);
            exit;
        }
        $response['success']  = 'KC';
        $response['message'] = 'Old Password Mismatch';
        echo json_encode($response);
        exit;
    }

}
