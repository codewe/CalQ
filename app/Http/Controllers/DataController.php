<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;

class DataController extends Controller {
	public function getBanks() {
		$output = [];

		$results = DB::table('atm')->where('bank', '<>', '')->select('bank')->get();
		$output['status'] = "success";
		foreach ($results as $result) {
			$output['banks'][] = $result->bank;
		}
		$output['banks'][] = "All";
		return $output;

	}

	public function getAtms(Request $request) {
		#ensure results are fetched as arrays
		DB::setFetchMode(\PDO::FETCH_ASSOC);
		$bank = $request->input('bank');
		if ($bank != "") {
			$atms = DB::table('atm')->where('bank', '=', $bank)->get();
		} else if ($bank == "" || $bank == "All") {
			$atms = DB::table('atm')->get();
		}
		#revert fetch mode to objects
		DB::setFetchMode(\PDO::FETCH_CLASS);

		$output = [];
		$user_lat = -1.3106914;
		$user_lon = 36.8070243;
		$output["status"] = "success";
		$output["user_lat"] = $user_lat;
		$output["user_lon"] = $user_lon;
		$output["atms"] = null;

		foreach ($atms as $atm) {
			$distance = $this->calcDistance($user_lat, $user_lon, $atm['lat'], $atm['lon'], 'K');
			$atm['distance'] = $distance . "KM";
			$output["atms"][] = $atm;
		}
		return $output;

	}
	public function getMpesas() {
		#ensure results are fetched as arrays
		DB::setFetchMode(\PDO::FETCH_ASSOC);

		$output = [];
		$user_lat = -1.3106914;
		$user_lon = 36.8070243;
		$output["status"] = "success";
		$output["user_lat"] = $user_lat;
		$output["user_lon"] = $user_lon;
		$output["mpesas"] = null;

		DB::table('mpesa')->chunk(100, function ($mpesas) use (&$output, $user_lat, $user_lon) {
			foreach ($mpesas as $mpesa) {
				$distance = $this->calcDistance($user_lat, $user_lon, $mpesa['lat'], $mpesa['lon'], 'K');
				$mpesa['distance'] = $distance . "KM";
				$output["mpesas"][] = $mpesa;
			}
		});
		return $output;
		#revert fetch mode to objects
		DB::setFetchMode(\PDO::FETCH_CLASS);

	}

	/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
/*::                                                                         :*/
/*::  This routine calculates the distance between two points (given the     :*/
/*::  latitude/longitude of those points). It is being used to calculate     :*/
/*::  the distance between two locations using GeoDataSource(TM) Products    :*/
/*::                                                                         :*/
/*::  Definitions:                                                           :*/
/*::    South latitudes are negative, east longitudes are positive           :*/
/*::                                                                         :*/
/*::  Passed to function:                                                    :*/
/*::    lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees)  :*/
/*::    lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees)  :*/
/*::    unit = the unit you desire for results                               :*/
/*::           where: 'M' is statute miles (default)                         :*/
/*::                  'K' is kilometers                                      :*/
/*::                  'N' is nautical miles                                  :*/
/*::  Worldwide cities and other features databases with latitude longitude  :*/
/*::  are available at http://www.geodatasource.com                          :*/
/*::                                                                         :*/
/*::  For enquiries, please contact sales@geodatasource.com                  :*/
/*::                                                                         :*/
/*::  Official Web site: http://www.geodatasource.com                        :*/
/*::                                                                         :*/
/*::         GeoDataSource.com (C) All Rights Reserved 2015		   		     :*/
/*::                                                                         :*/
/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
	private function calcDistance($lat1, $lon1, $lat2, $lon2, $unit) {

		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		$unit = strtoupper($unit);

		if ($unit == "K") {
			return number_format(($miles * 1.609344), 2, '.', "");
		} else if ($unit == "N") {
			return number_format(($miles * 0.8684), 2, '.', "");

		} else {
			return $miles;
		}
	}

	//
}
