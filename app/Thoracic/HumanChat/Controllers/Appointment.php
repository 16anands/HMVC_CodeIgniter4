<?php namespace App\Thoracic\HumanChat\Controllers;

use App\Thoracic\HumanChat\Models\ValidateUser;
use App\Thoracic\HumanChat\Models\AppointmentModel;
use App\Thoracic\HumanChat\Models\CalenderModel;

class Appointment extends BaseController{
    
    public function __construct(){
        $this->ValidateUser = new ValidateUser();
        $this->AppointmentModel = new AppointmentModel();
        $this->CalenderModel = new CalenderModel();   
    }
	
    private function inputhandle($in,$count=0){
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
        if(!isset($in['profile_id'])){
            //device timestamp
            $response['error']  = 'E002';
			$response['message'] = 'No Profile Received';
			echo json_encode($response);
			exit;
		}
        else{
            $id = $this->ValidateUser->matchHeading();
            if($id!=$in['profile_id']){
                $response['error']  = 'E003';
                $response['message'] = 'Authentication Failure';
                echo json_encode($response);
                exit;
            }
        }
    }
    
    public function getConfiguration(){
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),1);
        $configs=$this->AppointmentModel->fetchConfiguration($inward->profile_id); 
        $response['success']  = 'HC020';
        $response['message'] = 'Configuration Fetched';
        $response['payload'] = $configs;
        echo json_encode($response);
        exit;
    }
    
    public function getAppointment(){ //KAPI01
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),1);
        $enable=$this->AppointmentModel->fetchChatEnabled($inward->profile_id); 
        if($enable){
            $response['success']  = 'HC004';
            $response['message'] = 'Human Chat DISBALED';
            echo json_encode($response);
            exit;
        }
        $session=$this->AppointmentModel->fetchSessionStart($inward->profile_id); 
        if($session){
            $response['success']  = 'HC005';
            $response['message'] = 'Human Chat Enables After '.$session;
            echo json_encode($response);
            exit;
        }
        $adata=$this->AppointmentModel->fetchAppointment($inward->profile_id); 
        if($adata==2){
            $response['success']  = 'HC001';
			$response['message'] = 'No Booked Appointment';
			echo json_encode($response);
			exit;
        }
        if($adata==3){
            $response['error']  = 'E009';
			$response['message'] = 'Appointment Expiry Failed';
			echo json_encode($response);
			exit;
        }
        if($adata==4){
            $response['success']  = 'HC002';
            $response['message'] = 'Appointment Expired';
            echo json_encode($response);
            exit;
        }             
        $response['success']  = 'HC003';
        $response['message'] = 'Current Appointment';
        $response['payload'] = $adata;
        echo json_encode($response);
        exit;
	}
    
    public function getLastSummary(){ //KAPI02
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),1);
        $enable=$this->AppointmentModel->fetchChatSummary($inward->profile_id);
        if($enable){
            $response['success']  = 'HC009';
            $response['message'] = 'Chat Summary is DISBALED';
            echo json_encode($response);
            exit;
        }
        $summary=$this->AppointmentModel->fetchLastSummary($inward->profile_id);
        if($summary){
            $response['success']  = 'HC006';
            $response['message'] = 'Last Summary Pending';
            $response['payload'] = $summary;
            echo json_encode($response);
            exit;
        }
        $response['success']  = 'HC007';
        $response['message'] = 'Last Summary Not Pending';
        echo json_encode($response);
        exit;
    }

    public function setChatSummary(){ //KAPI03
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),3);
        if(is_null($inward->booking_guid) || is_null($inward->summary)){
            $response['error']  = 'E005';
            $response['message'] = 'Required Payload Not Send';
            echo json_encode($response);
            exit;              
        }
        $summary=$this->AppointmentModel->saveChatSummary($inward);
        if($summary){
            $response['success']  = 'HC008';
            $response['message'] = 'Summary Submitted';
            echo json_encode($response);
            exit;
        }
        $response['error']  = 'E004';
        $response['message'] = 'Summary Submit Failed';
        echo json_encode($response);
        exit;
    }

    public function getCoachList(){ //KAPI04
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),1);
        $list=$this->AppointmentModel->fetchCoach($inward->profile_id);
        if($list){
            $response['success']  = 'HC010';
            $response['message'] = 'Coach List Fetched';
            $response['payload'] = $list;
            echo json_encode($response);
            exit;
        }
        $response['error']  = 'E006';
        $response['message'] = 'No NOT Licensed';
        echo json_encode($response);
        exit;
    }

    public function getCoachAvailability(){ //KAPI05
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),4);
        if(is_null($inward->nextyear) || is_null($inward->nextmounth) || is_null($inward->coach_id)){
            $response['error']  = 'E005';
            $response['message'] = 'Required Payload Not Send';
            echo json_encode($response);
            exit;              
        }
        $availability = $this->CalenderModel->showCoach($inward);
        $response['success']  = 'HC011';
        $response['message'] = 'Coach Availability Fetched';
        $response['payload'] = $availability;
        echo json_encode($response);
        exit;
    }

    public function setAppointment(){ //KAPI06
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),7);
        if(is_null($inward->datevalue) || is_null($inward->month) || is_null($inward->year) || is_null($inward->startime) || is_null($inward->coach_id) || is_null($inward->type)){
            $response['error']  = 'E005';
            $response['message'] = 'Required Payload Not Send';
            echo json_encode($response);
            exit;              
        }

        $book=$this->AppointmentModel->saveAppointment($inward);
        if($book==2){
            $response['success']  = 'HC012';
            $response['message'] = 'Booked Slot Conflicts';
            echo json_encode($response);
            exit;
        }
        if($book){
            $adata=$this->AppointmentModel->fetchAppointment($inward->profile_id);
            $response['success']  = 'HC013';
            $response['message'] = 'Chat Booked Sucessfully';
            $response['payload'] = $adata;
            echo json_encode($response);
            exit;
        }
        $response['error']  = 'E007';
        $response['message'] = 'Chat Booking Failed';
        echo json_encode($response);
        exit;
    }

    public function setCancellation(){ //KAPI07
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),3);
        if(is_null($inward->booking_guid) || is_null($inward->reason)){
            $response['error']  = 'E005';
            $response['message'] = 'Required Payload Not Send';
            echo json_encode($response);
            exit;              
        }
        $cancel=$this->AppointmentModel->saveCancellation($inward);
        if($cancel){
            $response['success']  = 'HC014';
            $response['message'] = 'Appointment Cancelled';
            echo json_encode($response);
            exit;
        }
        $response['error']  = 'E010';
        $response['message'] = 'Appointment Cancel Failed';
        echo json_encode($response);
        exit;
    }

    public function getChatSummary(){ //KAPI08
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),2);
        if(is_null($inward->coach_id)){
            $response['error']  = 'E005';
            $response['message'] = 'Required Payload Not Send';
            echo json_encode($response);
            exit;              
        }
        $enable=$this->AppointmentModel->fetchChatSummary($inward->profile_id);
        if($enable){
            $response['success']  = 'HC009';
            $response['message'] = 'Chat Summary is DISBALED';
            echo json_encode($response);
            exit;
        }
        $summary=$this->AppointmentModel->fetchSummary($inward);
        if($summary){
            $response['success']  = 'HC015';
            $response['message'] = 'Summary Fetched';
            $response['payload'] = $summary;
            echo json_encode($response);
            exit;
        }
        $response['success']  = 'HC016';
        $response['message'] = 'No Summary';
        echo json_encode($response);
        exit;
    }

    public function getAvailabilityCoach(){ //KAPI10
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),3);
        if(is_null($inward->nextyear) || is_null($inward->nextmounth)){
            $response['error']  = 'E005';
            $response['message'] = 'Required Payload Not Send';
            echo json_encode($response);
            exit;              
        }
        $availability = $this->CalenderModel->showSlots($inward);
        $response['success']  = 'HC011';
        $response['message'] = 'Coach Availability Fetched';
        $response['payload'] = $availability;
        echo json_encode($response);
        exit;
    }

    public function getCoachListAll(){ //KAPI11
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),2);
        if(is_null($inward->uniquecoach) || empty($inward->uniquecoach)){
            $response['error']  = 'E005';
            $response['message'] = 'Required Payload Not Send';
            echo json_encode($response);
            exit;              
        }
        $lists=$this->AppointmentModel->fetchCoachAll($inward->uniquecoach);
        if($lists){
            $response['success']  = 'HC010';
            $response['message'] = 'Coach List Fetched';
            $response['payload'] = $lists;
            echo json_encode($response);
            exit;
        }
        $response['error']  = 'E011';
        $response['message'] = 'Coaches NOT Found';
        echo json_encode($response);
        exit;
    }

    public function setChatStart(){ //KAPI12
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),2); 
        if(is_null($inward->booking_guid)){
            $response['error']  = 'E005';
            $response['message'] = 'Required Payload Not Send';
            echo json_encode($response);
            exit;              
        }
        $start=$this->AppointmentModel->saveChatStart($inward->booking_guid);
        if($start){
            $response['success']  = 'HC018';
            $response['message'] = 'Chat Started';
            echo json_encode($response);
            exit;
        }
        $response['error']  = 'E012';
        $response['message'] = 'Chat Start Failed';
        echo json_encode($response);
        exit;
    }

    public function setChatEnds(){ //KAPI13
        $inward = $this->request->getJSON();
        $this->inputhandle(json_encode($inward),4); 
        if(is_null($inward->booking_guid) || is_null($inward->status) || is_null($inward->messageid)){
            $response['error']  = 'E005';
            $response['message'] = 'Required Payload Not Send';
            echo json_encode($response);
            exit;              
        }
        $start=$this->AppointmentModel->saveChatEnds($inward);
        if($start){
            $response['success']  = 'HC019';
            $response['message'] = 'Chat Ended';
            echo json_encode($response);
            exit;
        }
        $response['error']  = 'E013';
        $response['message'] = 'Chat End Failed';
        echo json_encode($response);
        exit;
    }
}
