<?php namespace App\Thoracic\ConvoAPI\Models;

use CodeIgniter\Model;

class CalenderModel extends Model{    
    
    public function __construct(){     
        $this->naviHref = htmlentities($_SERVER['PHP_SELF']);
        $this->db = \Config\Database::connect();
    }
    
    private $dayLabels = array("Mon","Tue","Wed","Thu","Fri","Sat","Sun");
    private $currentYear=0;
    private $currentMonth=0;
    private $currentDay=0;
    private $currentDate=null;
    private $daysInMonth=0;
    private $naviHref= null;
    
    public function showCoach($d) {
        if($d->nextmounth == 0){
            $month = date('m',time());  $year = date('Y',time());
        }else{
			$year  = $d->nextyear; $month = $d->nextmounth;
		}
		$datenum = strtotime($year.'-'.$month.'-5');
		$nextdatey = date('Y-m-d',$datenum+31*86400);
		$prevdatey = date('Y-m-d',$datenum-31*86400);
        $monthname = date('M',strtotime($year.'-'.$month.'-'.'1'));
        $this->currentYear=$year;
        $this->currentMonth=$month;
        $this->daysInMonth=$this->_daysInMonth($month,$year);  
        $calenderset = array();
		$calenderset['year'] = $year;
		$calenderset['month'] = $monthname;
		$calenderset['nextmounth'] = date('m',strtotime($nextdatey));
		$calenderset['nextyear'] = date('Y',strtotime($nextdatey));
		$calenderset['prevmounth'] = date('m',strtotime($prevdatey));
        $calenderset['prevyear'] = date('Y',strtotime($prevdatey));
        $builder = $this->db->table('coach_account');
        $query = $builder->select('name')
            ->where('coach_profile_id',$d->coach_id)
            ->get();
        $coach = $query->getResult();
		$calenderset['message'] = 'Currently, '.$coach[0]->name.' has no available slots for this month. Kindly check back later or check availability for the next month.';
		$currentdate = strtotime(date('Y-m-d'));
		$daysi = 0;
		$weeksInMonth = $this->_weeksInMonth($month,$year);
		for( $i=0; $i<$weeksInMonth; $i++ ){
			for($j=1;$j<=7;$j++){
				$day = $this->_showDay($i*7+$j);
				@$calenderset['days'][$daysi][$j]->datevalue = $day;
				$ddate = strtotime($year.'-'.$month.'-'.$day);
				if($ddate < $currentdate){
					$calenderset['days'][$daysi][$j]->status = 'disable';
					$calenderset['days'][$daysi][$j]->avability = array();
				}else{
					$calenderset['days'][$daysi][$j]->status = 'disable';
					$avilable = $this->getCoachAvabilitDate($ddate,$d->coach_id);
					$calenderset['days'][$daysi][$j]->avability = $avilable;
					if(count($calenderset['days'][$daysi][$j]->avability) > 0){
						$calenderset['days'][$daysi][$j]->status = 'enable'; 
						$calenderset['days'][$daysi][$j]->month = $monthname; 
						$calenderset['days'][$daysi][$j]->year = $year; 
						$calenderset['message'] = '';
					}
				}
			}
			$daysi++;
		}
		return $calenderset;
    }
	
	private function getCoachAvabilitDate($date,$coachid){
        $d = date('Y-m-d',$date);
        $builder = $this->db->table('coach_avability');
        $query = $builder->select('starttime,endtime')
            ->where('coach_profile_id',$coachid)
            ->where('avilabledate',$d)
            ->orderBy('starttime','asc')
            ->get();
        $result = $query->getResult();
		$slotlist = array();
		$i=0;
		$today = time()+21600;
		$datec = date('Y-m-d',$today);
		$timec = strtotime(date('H:i:s',$today));
        foreach($result as $row){
            $timestart = $row->starttime;
            while(strtotime($row->endtime) > strtotime($timestart)){
                $builder = $this->db->table('coach_booking');
                $builder->where('bookingdate',$d)
                    ->where('bookingtime',$timestart)
                    ->groupStart()
                        ->where('status =','active')
                        ->orWhere('status =','booked')
                    ->groupEnd();
                $booked = $builder->countAllResults();
                if($d > $datec){
                    if($booked > 0){
                        $timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
                    }else{
                        $slotlist[$i]['startime'] = $timestart;
                        $slotlist[$i]['startimetext'] = date('h : i     A',strtotime($timestart));
                        $timestartt = date("H:i:s",strtotime('+15 minutes',strtotime($timestart)));
                        $timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
                        $slotlist[$i]['endtimetext'] = date('h : i A',strtotime($timestartt));
                        $i++;
                    }
                }elseif($d == $datec){
                    if($timec < strtotime($timestart)){
                        if($booked > 0){
                            $timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
                        }else{
                            $slotlist[$i]['startime'] = $timestart;
							$slotlist[$i]['startimetext'] = date('h : i A',strtotime($timestart));
							$timestartt = date("H:i:s",strtotime('+15 minutes',strtotime($timestart)));
							$timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
							$slotlist[$i]['endtimetext'] = date('h : i A',strtotime($timestartt));
                            $i++;
                        }
                    }else{
                        $timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
                    }
                }else{
                    $timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
                }
            }
        }
		return $slotlist;
    }
    
    public function showSlots($d) {
		if($d->nextmounth == 0){
			$month = date('m',time());	$year = date('Y',time());
		}else{
			$year  = $d->nextyear;    $month = $d->nextmounth;
		}
		$datenum = strtotime($year.'-'.$month.'-5');
		$nextdatey = date('Y-m-d',$datenum+31*86400);
        $prevdatey = date('Y-m-d',$datenum-31*86400);
        $builder = $this->db->table('coachee');
        $query = $builder->select('license_guid')
            ->where('coachee_profile_id',$d->profile_id)
            ->get();
        $license = $query->getResult();
		$lid = $license[0]->license_guid;
        $monthname = date('M',strtotime($year.'-'.$month.'-'.'1'));
        $this->currentYear=$year;
        $this->currentMonth=$month;
        $this->daysInMonth=$this->_daysInMonth($month,$year);  
        $calenderset = array();
		$calenderset['year'] = $year;
		$calenderset['month'] = $monthname;
		$calenderset['nextmounth'] = date('m',strtotime($nextdatey));
		$calenderset['nextyear'] = date('Y',strtotime($nextdatey));
		$calenderset['prevmounth'] = date('m',strtotime($prevdatey));
		$calenderset['prevyear'] = date('Y',strtotime($prevdatey));
		$calenderset['message'] = 'All coaches are fully booked this month. Schedule for the next month or come back later to check availability.';
		$currentdate = strtotime(date('Y-m-d'));
		$daysi = 0;
        $weeksInMonth = $this->_weeksInMonth($month,$year);
        for( $i=0; $i<$weeksInMonth; $i++ ){
            for($j=1;$j<=7;$j++){
                $day = $this->_showDay($i*7+$j);
                @$calenderset['days'][$daysi][$j]->datevalue = $day;
                $ddate = strtotime($year.'-'.$month.'-'.$day);
				if($ddate < $currentdate){
                    $calenderset['days'][$daysi][$j]->status = 'disable';
                    $calenderset['days'][$daysi][$j]->avability = array();
                }else{
                    $calenderset['days'][$daysi][$j]->status = 'disable';
                    $avilable = $this->unique_timeslot($ddate,$lid);
                    $calenderset['days'][$daysi][$j]->avability =   $avilable;
                    if(count($calenderset['days'][$daysi][$j]->avability['slotlist']) > 0){
                        $calenderset['days'][$daysi][$j]->status = 'enable'; 
						$calenderset['days'][$daysi][$j]->month = $monthname; 
						$calenderset['days'][$daysi][$j]->year = $year; 
						$calenderset['message'] = '';
                    }
                }	
            }
            $daysi++;
        }	 
        return $calenderset;
    }
    
    public function unique_timeslot($date,$accountid){
        $date = date('Y-m-d',$date);
        $builder = $this->db->table('coach_avability');
        $query = $builder->select('starttime,coach_avability.coach_profile_id,endtime')
            ->where('coach_avability.avilabledate',$date)
            ->where('coach_link_license.license_guid',$accountid)
            ->join('coach_link_license','coach_link_license.coach_profile_id = coach_avability.coach_profile_id')
            ->get();
        $result = $query->getResult();
		$slotlist = array();$i=0;$slott = array();$coach = array();
		$today = time()+21600;$datec = date('Y-m-d',$today);
		$timec = strtotime(date('H:i:s',$today));
	    foreach ($result as $row){
            $timestart = $row->starttime;
            $coach_id = $row->coach_profile_id;
            while(strtotime($row->endtime) > strtotime($timestart)){
                $builder = $this->db->table('coach_booking');
                $builder->where('bookingdate',$date)
                    ->where('bookingtime',$timestart)
                    ->where('coach_profile_id',$coach_id)
                    ->groupStart()
                        ->where('status =','active')
                        ->orWhere('status =','booked')
                    ->groupEnd();
                $booked = $builder->countAllResults();
                if($date > $datec){ 
                    if($booked > 0){
                        $timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
					}else{
                        $slott[] = $timestart;
						$coach[] = $coach_id;
						$slotlist[$i]['coach_id'] = $coach_id;
						$slotlist[$i]['startime'] = $timestart;
						$slotlist[$i]['startimetext'] = date('h : i A',strtotime($timestart));
						$timestartt = date("H:i:s",strtotime('+15 minutes',strtotime($timestart)));
						$timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
						$slotlist[$i]['endtimetext'] = date('h : i A',strtotime($timestartt));
						$i++;
                    }
                }elseif($date == $datec){
                    if($timec < strtotime($timestart)){
                        if($booked > 0){
                            $timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
                        }else{
                            $slott[] = $timestart;
                            $coach[] = $coach_id;
                            $slotlist[$i]['coach_id'] = $coach_id;
                            $slotlist[$i]['startime'] = $timestart;
                            $slotlist[$i]['startimetext'] = date('h : i A',strtotime($timestart));
                            $timestartt = date("H:i:s",strtotime('+15 minutes',strtotime($timestart)));
                            $timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
                            $slotlist[$i]['endtimetext'] = date('h : i A',strtotime($timestartt));
                            $i++;
                        }
                    }else{
                        $timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
                    }
                }else{
                    $timestart = date("H:i:s",strtotime('+30 minutes',strtotime($timestart)));
                }
            }  
        }
        $uniquetime = array_unique($slott);
		$uniquecoch = array_unique($coach);
		$timeslotdata = array();
		$i = 0;
		foreach($uniquetime as $row){
			$timeslotdata[$i]['timeslot'] = $row;
			$timeslotdata[$i]['starttimetext'] = date("h : i A",strtotime('+0 minutes',strtotime($row)));
			$timeslotdata[$i]['endtimetext'] = date("h : i A",strtotime('+15 minutes',strtotime($row)));
			$i++;
        }
        $dataset['uniquetime'] = $timeslotdata;
		$dataset['slotlist'] = $slotlist;
		$dataset['uniquecoach'] = $uniquecoch;
        return $dataset;
    }
    
    /********************* NO CHANGE BEYOND **********************/ 
    /**create the li element for ul*/
    private function _showDay($cellNumber){
        if($this->currentDay==0){
            $firstDayOfTheWeek = date('N',strtotime($this->currentYear.'-'.$this->currentMonth.'-01'));       
            if(intval($cellNumber) == intval($firstDayOfTheWeek)){  
                $this->currentDay=1;  
            }
        }
        if( ($this->currentDay!=0)&&($this->currentDay<=$this->daysInMonth) ){
            $this->currentDate = date('Y-m-d',strtotime($this->currentYear.'-'.$this->currentMonth.'-'.($this->currentDay)));
            $cellContent = $this->currentDay;
            $this->currentDay++;     
        }else{
            $this->currentDate =null;
            $cellContent=null;
        }
        return $cellContent;
    }
    
    /*** create navigation*/
    private function _createNavi(){
        $nextMonth = $this->currentMonth==12?1:intval($this->currentMonth)+1;
        $nextYear = $this->currentMonth==12?intval($this->currentYear)+1:$this->currentYear;
        $preMonth = $this->currentMonth==1?12:intval($this->currentMonth)-1;
        $preYear = $this->currentMonth==1?intval($this->currentYear)-1:$this->currentYear;
        return
            '<div class="header">'.
            '<a class="prev" href="'.$this->naviHref.'?month='.sprintf('%02d',$preMonth).'&year='.$preYear.'">Prev</a>'.
            '<span class="title">'.date('Y M',strtotime($this->currentYear.'-'.$this->currentMonth.'-1')).'</span>'.
            '<a class="next" href="'.$this->naviHref.'?month='.sprintf("%02d", $nextMonth).'&year='.$nextYear.'">Next</a>'.
            '</div>';
    }
         
    /*** create calendar week labels*/
    private function _createLabels(){         
        $content='';
        foreach($this->dayLabels as $index=>$label){
            $content.='<li class="'.($label==6?'end title':'start title').' title">'.$label.'</li>';
        }
        return $content;
    }
    /*** calculate number of weeks in a particular month*/
    private function _weeksInMonth($month=null,$year=null){
        if( null==($year) ) {
            $year =  date("Y",time()); 
        }
        if(null==($month)) {
            $month = date("m",time());
        }
        // find number of days in this month
        $daysInMonths = $this->_daysInMonth($month,$year);
        $numOfweeks = ($daysInMonths%7==0?0:1) + intval($daysInMonths/7);
        $monthEndingDay= date('N',strtotime($year.'-'.$month.'-'.$daysInMonths));
        $monthStartDay = date('N',strtotime($year.'-'.$month.'-01'));
        if($monthEndingDay<$monthStartDay){
            $numOfweeks++;
        }
        return $numOfweeks;
    }
 
    /*** calculate number of days in a particular month*/
    private function _daysInMonth($month=null,$year=null){
        if(null==($year))
            $year =  date("Y",time()); 
        if(null==($month))
            $month = date("m",time());
             
        return date('t',strtotime($year.'-'.$month.'-01'));
    }
     
}
?>