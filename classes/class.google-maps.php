<?php

@ set_time_limit(0);

/*
 * This class will determine the distance of two geo loaction by zip, street address
 * */

class GeoLocation{
	
	private $from;
	private $to;
	private $unit;
	
	//initialize the location class
	function __construct($from=null, $to=null, $unit = null){
		$this->from = $from;
		$this->to = $to;
		$this->unit = $unit;
	}
	
	
	//retun the distance 
	public function get_distance(){
		return $this->Haversine($this->get_lat_long($this->from), $this->get_lat_long($this->to));
	}
	
	
	//get teh latitude and longitude
	private function get_lat_long($address){
	    $address = str_replace(' ', '+', $address);
	    $url = 'http://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false';
	 
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    $geoloc = curl_exec($ch);
	 
	    $json = json_decode($geoloc);
	    return array($json->results[0]->geometry->location->lat, $json->results[0]->geometry->location->lng);
	}
	
	
	//complex algorithm to return the distance. You don't need to understand this.
	// if you have have mathmatical background, you may try to understand the algorithm
	private function Haversine($start, $finish){
		
	    $theta = $start[1] - $finish[1]; 
	    $distance = (sin(deg2rad($start[0])) * sin(deg2rad($finish[0]))) + (cos(deg2rad($start[0])) * cos(deg2rad($finish[0])) * cos(deg2rad($theta))); 
	    $distance = acos($distance); 
	    $distance = rad2deg($distance); 
	    $distance = $distance * 60 * 1.1515; 
	 
	    return $this->fomratted_distance($distance);
	}
	
	
	//format it looking at unit
	private function fomratted_distance($distance){
		switch($this->unit){
			case 'mile' :
				$d = $distance;
				break;
			default: 
				$d = $distance * 1.609344;
				break;
		}
		
		return round($d, 2);
	}
	
	
	//utlity function to set the from and to
	function set_to($to){
		$this->to = $to;
	}
	
	function set_from($from){
		$this->from = $from;
	}
	
	function set_unit($unit){
		$this->set_unit($unit);
	}
}