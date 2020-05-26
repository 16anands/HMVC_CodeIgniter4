<?php namespace App\Thoracic\HumanChat\Models;

use CodeIgniter\Model;
use DateTime;
use DateTimeZone;
use \App\Libraries\GlobalIdentity;

class AppointmentModel extends Model {
    
    public function __construct(){
        $this->genGUID = GlobalIdentity::genGUID();
        $this->timezone = 'Asia/Kolkata';
        $this->instance = new DateTime('now', new DateTimeZone($this->timezone));
        $this->datetime = $this->instance->format('Y-m-d H:i:s');
        $this->date = $this->instance->format('Y-m-d');
        $this->db = \Config\Database::connect();
    }
    
    private function fetchLicense($id){
        $builder = $this->db->table('coachee');
        $query = $builder->select('license_guid')
            ->where('coachee_profile_id',$id)
            ->get();
        $license = $query->getResult();
        return $license[0]->license_guid;
    }
    
    private function fetchActive($bookingdate,$bookingtime){
        $timeuser =  strtotime($bookingdate.' '.$bookingtime);
        $timeserver = strtotime($this->datetime)+8*60;
        $difftime = $timeuser - $timeserver;
        return $difftime;
    }

    private function expireChat($booking_guid){
        $builder = $this->db->table('coach_booking');
        $expire = array(
            'status'=>'expired',
            'updated_on'=>$this->datetime
        );
        $builder->where('booking_guid',$booking_guid);
        $builder->update($expire);
        return $this->db->affectedRows();
    } 

    private function fetchChatReport($id){
        $license_guid = $this->fetchLicense($id);
        $builder = $this->db->table('license');
        $query = $builder->select('chatemail')
            ->where('license_guid',$license_guid)
            ->get();
        $chat = $query->getResult();
        if($chat[0]->chatemail==1){
            return false;
        }
        return true;
    }

    private function sendChatReport($booking_guid){
        $data = $this->fetchChatHistory($booking_guid);
        $data['conversation'] = (object) $data;
        $builder = $this->db->table('coach_booking');
        $query = $builder->select('profile_id,type,coach_profile_id')
            ->where('booking_guid',$booking_guid)
            ->get();
        $id = $query->getResult();
        $profile_id = $id[0]->profile_id;
        $coach_id = $id[0]->coach_profile_id;
        if($id[0]->type=='CHAT'){
            //Coachee Details
            $builder = $this->db->table('coachee');
            $query = $builder->select('coachee_name,coachee_email,account_guid')
                ->where('coachee_profile_id',$profile_id)
                ->get();
            $coacheeprofile = $query->getResult();
            $coacheeprofile = $coacheeprofile[0];
            $data['conversation']->coachee = (object) $coacheeprofile;
            //Coach Details
            $builder = $this->db->table('coach_account');
            $query = $builder->select('name,email')
                ->where('coach_profile_id',$coach_id)
                ->get();
            $coachprofile = $query->getResult();
            $coachprofile = $coachprofile[0];
            $data['conversation']->coach = (object) $coachprofile;
            // Account Details
            $builder = $this->db->table('account');
            $query = $builder->select('org_name')
                ->where('account_guid', $coacheeprofile->account_guid)
                ->get();
            $orgName = $query->getResult();
            $orgName = $orgName[0];
            $data['conversation']->account = (object) $orgName;
            //Emailer Setup Config
            $from = 'support@koach.ai';
            $subject = 'Coach Chat Report';
            if (getenv()=='production') {
                $to = $coacheeprofile->coachee_email;
            } else {
                $to = 'ashima@koach.ai,anand@koach.ai,majeed@koach.ai';
            }
            $cc = $coachprofile->email;
            //Email Formatter 
            $html = view('ChatReport', $data, true);
            //Email Sender
            // $this->mailChatReport($from,$to,$subject,$html,$cc);
        }
        return true;        
    }

    private function fetchChatHistory($booking_guid){
        $builder = $this->db->table('chat_history_human');
        $query = $builder->select('*')
            ->where('conversation_id_human', $booking_guid)
            ->get();
        $result = $query->getResult();
        $outward = array();
        $i=0;
        foreach ($result as $key=>$row) {
            $row->options = json_decode($row->options);
            $outward['conversation'][$i] = $row;
            $i++;
        }
        return $outward;
    }

    // private function mailChatReport($from,$to,$subject,$data,$cc){
    //     if($cc){$to=$to.','.$cc;}
    //     $this->load->library('emailer/emailer');
    //     $datas = array(
    //         'to' => $to,
    //         'subject' => $subject,
    //         'message' => $data,
    //         'alt_message' => ''
    //     );
    //     $this->emailer->send($datas, false);
    //     return true;
    // }

    public function fetchConfiguration($id){ 
        $license_guid = $this->fetchLicense($id);
        $builder = $this->db->table('license');
        $query = $builder->select('chatsummary,mentorcall')
            ->where('license_guid',$license_guid)
            ->get();
        $chatconfig = $query->getResult();
        $outward['chatsummary']='inactive';
        $outward['mentorcall']='inactive';
        if($chatconfig[0]->chatsummary==1){
            $outward['chatsummary']='active';
        }
        if($chatconfig[0]->mentorcall==1){
            $outward['mentorcall']='active';
        }
        return $outward;
    }
	   
    public function fetchChatEnabled($id){ 
        $license_guid = $this->fetchLicense($id);
        $builder = $this->db->table('license');
        $query = $builder->select('enable_human_coach')
            ->where('license_guid',$license_guid)
            ->get();
        $chatconfig = $query->getResult();
        if($chatconfig[0]->enable_human_coach==1){
            return false;
        }
        return true;
    }
    
    public function fetchSessionStart($id){ 
        $license_guid = $this->fetchLicense($id);
        $builder = $this->db->table('license');
        $query = $builder->select('coach_start_session')
            ->where('license_guid',$license_guid)
            ->get();
        $sess = $query->getResult();
        $builder = $this->db->table('coachee_activity');
        $builder->where('parameter',$sess[0]->coach_start_session)
            ->where('status','DONE')
            ->where('profile_id',$id); 
        if($builder->countAllResults()==0){
            return $sess[0]->coach_start_session;
        }
        return false;
    }
    
    public function fetchAppointment($id){ 
        $builder = $this->db->table('coach_booking');
        $query = $builder->select('booking_guid,bookingdate,bookingtime,type,coach_profile_id')
            ->where('bookingdate >=',$this->date)
            ->where('status','booked')
            ->orWhere('status','active')
            ->where('profile_id',$id)
            ->orderBy('id','desc')
            ->get();
        $bookings = $query->getResult();
        if(empty($bookings)){
            return 2; //No Appointment
        }
        else{
            $startchat = $this->fetchActive($bookings[0]->bookingdate, $bookings[0]->bookingtime);
            if($startchat<-1801){ // Expired
                $expire = $this->expireChat($bookings[0]->booking_guid);
                return $expire+3;
            }
            $outward['booking_guid']=$bookings[0]->booking_guid;
            $outward['start']=0;$outward['cancel']=1;
            if($startchat<=0 && $startchat>-1800){$outward['start']=1;} 
            if(getenv()=='production'){$outward['start']=1;}
            $outward['booking_date'] = date('dS F Y', strtotime($bookings[0]->bookingdate));
            $outward['booking_day'] = date("l", strtotime($bookings[0]->bookingdate));
            $outward['booking_type']=$bookings[0]->type;
            $outward['booking_time'] = date('h:i A', strtotime($bookings[0]->bookingtime));
            $outward['booking_next'] = date('h:i A', strtotime($bookings[0]->bookingtime)+15*60);
            $outward['coach_id'] = $bookings[0]->coach_profile_id;
            $builder = $this->db->table('coach_account');
            $query = $builder->select('name,contact_number,image,short_description')
                ->where('coach_profile_id',$outward['coach_id'])
                ->get();
            $coachdata = $query->getResult();
            $outward['coach_name'] = $coachdata[0]->name;
            $outward['coach_phone'] = $coachdata[0]->contact_number;
            $outward['coach_description'] = $coachdata[0]->short_description;
            $outward['coach_image'] = $coachdata[0]->image;
            if ($coachdata[0]->image == null) {
                $outward['coach_image'] =   base_url().'assets/dist/img/blankimage.png';
            }
            return $outward;
        }
    }

    public function fetchChatSummary($id){
        $license_guid = $this->fetchLicense($id);
        $builder = $this->db->table('license');
        $query = $builder->select('chatsummary')
            ->where('license_guid',$license_guid)
            ->get();
        $chatconfig = $query->getResult();
        if($chatconfig[0]->chatsummary==1){
            return false;
        }
        return true;
    }

    public function fetchLastSummary($id){
        $builder = $this->db->table('coach_booking');
        $query = $builder->select('booking_guid')
            ->where('profile_id',$id)
            ->where('status','endchat')
            ->orderBy('id','DESC')
            ->limit(1)
            ->get();
        $bookings = $query->getResult();
        if(empty($bookings)){
            return false;
        }
        $builder = $this->db->table('chat_summary');
        $builder->where('chat_id',$bookings[0]->booking_guid);
        if($builder->countAllResults()==0){
            return $bookings[0];
        }
        return false;  
    }

    public function saveChatSummary($d){
        $builder = $this->db->table('coach_booking');
        $query = $builder->select('coach_profile_id as coach')
            ->where('booking_guid', $d->booking_guid)
            ->get();
        $id = $query->getResult();
        $builder = $this->db->table('chat_summary');
        $chatsummarydata = array(
            'coachee_id'=>$d->profile_id,
            'coach_id'=>$id[0]->coach,
            'chat_id'=>$d->booking_guid,
            'summary'=>$d->summary
        );
        $builder->insert($chatsummarydata);
        return $this->db->affectedRows();
    }

    public function fetchCoach($id){
        $license_guid = $this->fetchLicense($id);
        $builder = $this->db->table('coach_link_license');
        $query = $builder->select('coach_profile_id as cid')
            ->where('license_guid',$license_guid)
            ->get();
        $coach = $query->getResult();
        if(empty($coach)){
            return false;
        }
        $i=0; $outward=array();
        foreach($coach as $key => $row) {
            $builder = $this->db->table('coach_account');
            $query = $builder->select('name,contact_number,image,short_description')
                ->where('coach_profile_id',$row->cid)
                ->where('status','1')
                ->get();
            $coachdata = $query->getResult();
            if(!empty($coachdata)){
                $coachdata[0]->coach_id=$row->cid;
                if ($coachdata[0]->image == null) {
                    $coachdata[0]->image =   base_url().'assets/dist/img/blankimage.png';
                }
                $outward[$i++]=$coachdata[0];
            } 
        }
        return $outward;
    }

    public function saveAppointment($d){
        $bookingDate = date('Y-m-d', strtotime($d->datevalue.'-'.$d->month.'-'.$d->year));
        $bookingTime = $d->startime;
        $builder = $this->db->table('coach_booking');
        $builder->where('bookingdate',$bookingDate)
                ->where('bookingtime',$bookingTime)
                ->where('coach_profile_id',$d->coach_id)
                ->groupStart()
                    ->where('status =','active')
                    ->orWhere('status =','booked')
                ->groupEnd();
        if($builder->countAllResults()==0){
            $builder = $this->db->table('coach_booking');
            $bookingdata = array(
                'booking_guid'=>$this->genGUID,
                'profile_id'=>$d->profile_id,
                'coach_profile_id'=>$d->coach_id,
                'bookingdate'=> $bookingDate,
                'bookingtime'=> $bookingTime,
                'type'=>$d->type,
                'created_on'=>$this->datetime
            );
            $builder->insert($bookingdata);
            return $this->db->affectedRows();
        }
        return 2;
    }

    public function saveCancellation($d){
        $builder = $this->db->table('coach_booking');
        $canceldata = array(
            'message'=>$d->reason,
            'status'=>'cancelled',
            'cancelationdatetime'=>$this->datetime,
            'updated_on'=>$this->datetime
        );
        $builder->where('booking_guid',$d->booking_guid);
        $builder->update($canceldata);
        return $this->db->affectedRows();
    }

    public function fetchSummary($d){
        $builder = $this->db->table('chat_summary');
        $query = $builder->select('summary,created_on,chat_id')
            ->where('coachee_id',$d->profile_id)
            ->where('coach_id',$d->coach_id)
            ->orderBy('id', 'DESC')
            ->get();
        $summarys = $query->getResult();
        if (!empty($summarys)) {
            for($i=0;$i<sizeof($summarys);$i++){
                $builder = $this->db->table('coach_booking');
                $query = $builder->select('type')
                    ->where('booking_guid',$summarys[$i]->chat_id)
                    ->get();
                $types = $query->getResult();
                if (!empty($types)) {
                    $summarys[$i]->chat_id = $types[0]->type;
                }
                $outward['summary'] = $summarys;
            }
            return $outward;
        }
        return false;
    }

    public function fetchCoachAll($id){
        $i=0; $outward=array();
        for($X=0;$X<sizeof($id);$X++){
            $builder = $this->db->table('coach_account');
            $query = $builder->select('name,contact_number,image,short_description')
                ->where('coach_profile_id',$id[$X])
                ->where('status','1')
                ->get();
            $coachdata = $query->getResult();
            if(!empty($coachdata)){
                $coachdata[0]->coach_id=$row->cid;
                if ($coachdata[0]->image == null) {
                    $coachdata[0]->image =   base_url().'assets/dist/img/blankimage.png';
                }
                $outward[$i++]=$coachdata[0];
            } 
        }
        return $outward;
    }
   
    public function saveChatStart($booking_guid){
        $builder = $this->db->table('coach_booking');
        $updatedata = array(
            'startedtime'=>date('Y-m-d H:i:s'),
            'status'=>'active',
            'updated_on'=>date('Y-m-d H:i:s')
        );
        $builder->where('booking_guid',$booking_guid);
        $builder->update($updatedata);
        return $this->db->affectedRows();
    }

    public function saveChatEnds($d){
        $endchat='invalid';
        if($d['status'] == 'VALID'){
            $endchat='endchat';
        }
        $summary=$this->fetchChatSummary($d->profile_id);
        if($summary || $endchat=='invalid'){
            $text = 'The chat has ended. Click on Continue to go to the dashboard.';
        }
        else{
            $text = 'The chat has ended. Click on Continue to submit summary.';
        }
        $options = array();
        $time = time();
        $builder = $this->db->table('chat_history_human');
        $insertdata = array(
            'chat' => $text,
            'conversation_id_human' => $d->booking_guid,
            'sessionid' => $d->booking_guid,
            'conv_done_by' => 'coach',
            'options' => '[{"option_text":"Continue","chatend":"ENDCHAT"}]',
            'flashcardreply' => '0',
            'messageid' => $d->messageid,
            'timestamp'=> $time
        );
        $builder->insert($insertdata);
        $chatreport=$this->fetchChatReport($d->profile_id);
        if(!$chatreport){
            $this->sendChatReport($d->booking_guid);
        }
        $builder = $this->db->table('coach_booking');
        $updatedata = array(
            'endtime'=>$this->datetime,
            'status'=>$endchat,
            'updated_on'=>$this->datetime
        );
        $builder->where('booking_guid',$d->booking_guid);
        $builder->update($updatedata);
        return $this->db->affected_rows();
    }
    
}