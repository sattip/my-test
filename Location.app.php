<?php

/**
 * Application Description...
 *
 * @package SkaDate5
 * @version 5.0
 * @since 5.0
 * @link http://www.skadate.com/
 * @author panosbobos
 */

class app_Location
{
	public static function Countries()
	{
		$_query = "SELECT `Country_str_code`, `Region_str_code`, SUBSTRING( `Country_str_name`, 1, 33 ) AS `Country_str_name` FROM `".TBL_LOCATION_COUNTRY."` 
			ORDER BY `Country_str_name`";
		
		return MySQL::fetchArray( $_query );
	}
	
	public static function getCountryname($country_code)
	{
		$_query = sql_placeholder( "SELECT `Country_str_name` FROM `".TBL_LOCATION_COUNTRY."` 
			WHERE `Country_str_code`=? LIMIT 1", $country_code );
		
		return SK_MySQL::query($_query)->fetch_cell();
	}
	
	public static function getStatename($country_code)
	{
		$_query = sql_placeholder( "SELECT `Admin1_str_name` FROM `".TBL_LOCATION_STATE."` 
			WHERE `Admin1_str_code`=? LIMIT 1", $country_code );
		
		return SK_MySQL::query($_query)->fetch_cell();
	}
	
	public static function Regions()
	{
		$_query = "SELECT * FROM `".TBL_LOCATION_REGION."`";
		$_region_info = MySQL::fetchArray( $_query );
		
		if ( is_array( $_region_info ) )
		{
			$_compiled_query = sql_compile_placeholder( "SELECT * FROM `".TBL_LOCATION_COUNTRY."` WHERE `Region_str_code`=?" );
			foreach ( $_region_info as $_key => $_region )
			{
				$_query = sql_placeholder( $_compiled_query, $_region['Region_str_code'] );
				$_region_info[$_key]['countries'] = MySQL::fetchArray( $_query );
			}
		}
		
		return $_region_info;
	}
	
	public static function isCountryZipExists( $country_id )
	{
		if ( !strlen( trim( $country_id ) ) )
			return false;
			
		$_query = sql_placeholder( "SELECT `zip` FROM `".TBL_LOCATION_ZIP."` 
			WHERE `country_id`=? LIMIT 1", $country_id );
		
		return ( MySQL::fetchField( $_query ) ) ? true : false;
	}
	
	public static function isCountryStateExists( $country_id )
	{
		if ( !strlen( trim( $country_id ) ) )
			return false;
			
		$_query = sql_placeholder( "SELECT `Admin1_str_code` FROM `".TBL_LOCATION_STATE."` 
			WHERE `Country_str_code`=? LIMIT 1", $country_id );
		
		return ( MySQL::fetchField( $_query ) ) ? true : false;
	}
	
	public static function getStaties( $country_id )
	{
		if ( !strlen( trim( $country_id ) ) )
			return array();
		
        $other = SK_Language::text("%label.other_states");

		$_query = sql_placeholder( "SELECT `Admin1_str_code`, IF( `Admin1_str_name`='' OR `Admin1_str_name`='undetermined' OR `Admin1_str_name`='(none)', ?, `Admin1_str_name` ) AS `Admin1_str_name`
			FROM `".TBL_LOCATION_STATE."` WHERE `Country_str_code`=? ORDER BY `Admin1_str_name`", mysql_real_escape_string($other), $country_id );
		
		return MySQL::fetchArray( $_query );
	}
	
	public static function isStateCityExists( $state_id )
	{
		if ( !strlen( trim( $state_id ) ) )
			return false;
			
		$_query = sql_placeholder( "SELECT `Feature_int_id` FROM `".TBL_LOCATION_CITY."` 
			WHERE `Admin1_str_code`=? LIMIT 1", $state_id );
		
		return ( MySQL::fetchField( $_query ) ) ? true : false;
	}
	
	public static function Cities( $state_id, $letter = null )
	{
		if ( !strlen( trim( $state_id ) ) )
			return false;
			
		if ( strlen( $letter ) == 1 )
			$_letter_cond = " AND `Feature_str_name` LIKE '$letter%'";
			
		$_query = sql_placeholder( "SELECT * FROM `".TBL_LOCATION_CITY."` 
			WHERE `Admin1_str_code`=? $_letter_cond
			ORDER BY `Feature_str_name`", $state_id );

		return MySQL::fetchArray( $_query, 'Feature_int_id' );
	}
	
	public static function CityNameById($city_id)
	{
		if (!isset($city_id)) {
			return null;
		}
		$query = SK_MySQL::placeholder(
		'SELECT `city`.`Feature_str_name` FROM `' . TBL_LOCATION_CITY . '` AS `city`
			WHERE `city`.`Feature_int_id`=?',$city_id);
		return SK_MySQL::query($query)->fetch_cell();
	}
	
	public static function isZipExists( $country_id, $zip )
	{
		if ( !strlen( trim( $zip ) ) || !strlen( trim( $country_id ) ) )
			return false;
			
		$_query = SK_MySQL::placeholder( "SELECT `country_id` FROM `".TBL_LOCATION_ZIP."` WHERE `zip`='?'", $zip );
		
		return ( MySQL::fetchField( $_query ) == $country_id ) ? true : false;
	}
	
	public static function isStateExist( $country_id, $state_id )
	{
		if ( !strlen( trim( $country_id ) ) || !strlen( trim( $state_id ) ) )
			return false;
		
		$_query = sql_placeholder( "SELECT `country`.`Country_str_code` FROM `".TBL_LOCATION_STATE."` AS `state`
			LEFT JOIN `".TBL_LOCATION_COUNTRY."` AS `country` USING( `Country_str_code` ) 
			WHERE `state`.`Admin1_str_code`=?", $state_id );
		
		return ( MySQL::fetchField( $_query ) == $country_id ) ? true: false;
	}
	
	public static function isCityExists( $state_id, $city_id )
	{
		if ( !strlen( trim( $state_id ) ) || !strlen( trim( $city_id ) ) )
			return false;
			
		$_query = sql_placeholder( "SELECT `state`.`Admin1_str_code` FROM `".TBL_LOCATION_CITY."` AS `city` 
			LEFT JOIN `".TBL_LOCATION_STATE."` AS `state` USING( `Admin1_str_code` ) 
			WHERE `city`.`Feature_int_id`=?", $city_id );
		
		return  ( MySQL::fetchField( $_query ) == $state_id ) ? true : false;
	}
	
	public static function CityByZip( $country_id, $zip )
	{
		if ( !strlen( trim( $country_id ) ) || !strlen( trim( $zip ) ) )
			return '';
			
		$_query = SK_MySQL::placeholder( "SELECT `city_id` FROM `".TBL_LOCATION_ZIP."`
			WHERE `country_id`='?' AND `zip`='?'", $country_id, $zip );
		
		return MySQL::fetchField( $_query );
	}
	
	public static function StateByZip( $country_id, $zip )
	{
		if ( !strlen( trim( $country_id ) ) || !strlen( trim( $zip ) ) )
			return '';
			
		$_query = SK_MySQL::placeholder( "SELECT `state_id` FROM `".TBL_LOCATION_ZIP."`
			WHERE `country_id`='?' AND `zip`='?'", $country_id, $zip );
		
		return MySQL::fetchField( $_query );
	}
	
	public static function ifAnyZipExists()
	{
		$_query = "SELECT `zip` FROM `".TBL_LOCATION_ZIP."` LIMIT 1";
		
		return ( MySQL::fetchField( $_query ) ) ? true : false;
	}
	
	public static function ifOnlyOneCountryExists()
	{
		$_query = "SELECT COUNT( `Country_str_code` ) FROM `".TBL_LOCATION_COUNTRY."`";
		
		return ( MySQL::fetchField( $_query ) == 1 ) ? true: false; 
	}
	
	public static function getSelectCityHref( $state_id )
	{
		return "window.open('".URL_HOME."select_city_popup.php?state_id=".$state_id."', 'im' + parseInt(Math.random()*100000), 'width=730,height=560,left=100,top=100,copyhistory=no,directories=no,menubar=no,location=no,resizable=no,scrollbars=yes');";
	}
	
	public static function StateLetters( $state_id )
	{
		$state_id = trim( $state_id );
		
		if ( !$state_id )
			return array();
			
		$_query = sql_placeholder( "SELECT `Letters` FROM `".TBL_LOCATION_STATE_LETTERS."`
			WHERE `Admin1_str_code`=?", $state_id );
		
		return MySQL::fetchField( $_query );
	}
	
	public static function CityCodeByName( $state_id, $city_str_name )
	{
		$city_str_name = trim( $city_str_name );
		$state_id = trim( $state_id );
		
		if ( !strlen( $city_str_name ) )
			return '';
			
		if ( !strlen( $state_id ) )
			return '';
		$_query = sql_placeholder( "SELECT `Feature_int_id` FROM `".TBL_LOCATION_CITY."` 
			WHERE `Feature_str_name`=? AND `Admin1_str_code`=?", $city_str_name, $state_id );
		
		return MySQL::fetchField( $_query );
	}
	
	public static function CitiesByZip($country_id, $zip)
	{
		if ( !strlen( trim( $zip ) ) )
			return false;
		
		$zip=mysql_real_escape_string($zip);		
		$_query = "SELECT `cities`.`Feature_str_name` AS `city_name` , `cities`.`Feature_int_id` AS `city_id`  FROM `".TBL_LOCATION_ZIP."` as zips INNER JOIN `".TBL_LOCATION_CITY."` as cities ON ( zips.city_id = cities.Feature_int_id) 
			WHERE zips.`zip`='".$zip."' AND zips.`country_id`='".$country_id."'";
		

		return MySQL::fetchArray( $_query);
	}
	
	public static function getSuggestCities($str, $state_id)
	{
		if (!strlen(trim($str)) || !strlen(trim($state_id))) {
			return array();
		}
		
		$query = SK_MySQL::placeholder('
				SELECT `t`.`Feature_int_id` AS `id`, `t`.`Feature_str_name` AS `name` FROM `' . TBL_LOCATION_CITY . '` AS `t`
				WHERE `t`.`Admin1_str_code`="?"
				AND `t`.`Feature_str_name` LIKE "?" LIMIT 0, 10'
				, $state_id,"$str%");
		$result = SK_MySQL::query($query);
		$out = array();
		while ($item = $result->fetch_object()){
			preg_match('/'.$str.'/i',$item->name,$matches);
			
			$label = '<b>'.$matches[0].'</b>'.substr($item->name,strlen($matches[0]));
			$out[] = array(
					'id'=>$item->id,
					'name'=>$label,
					'suggest_label'=>$label
				);
		}
		return $out;
	}
	
	public static function getSaggestCities($str, $state_id)
	{
		if (!strlen(trim($str)) || !strlen(trim($state_id))) {
			return array();
		}
		$ip = $_SERVER['REMOTE_ADDR'];
		$country_2char = app_Location::getCountryByIp($ip);
		
		$query = SK_MySQL::placeholder('
				SELECT `t`.`Feature_int_id` AS `id` , `t`.`Feature_str_name` AS `name` , `t`.`Admin1_str_code` AS `state_code` FROM  `' . TBL_LOCATION_CITY . '` AS `t`
				WHERE `t`.`Country_str_code`="?"
				AND `t`.`Feature_str_name` LIKE "?" LIMIT 0, 10'
				, $country_2char,"$str%");
		$result = SK_MySQL::query($query);
		$out = array();
		//$_item = $result->fetch_object();
		$_res = app_Location::getCountryname($country_2char);
		while ($item = $result->fetch_object()){
			preg_match('/'.$str.'/i',$item->name,$matches);
			$state_code = $item->state_code;
		   $state_name = app_Location::getStatename($state_code);
			//$country_name = print_r($_res);
			$label = '<b>'.$matches[0].'</b>'.substr($item->name,strlen($matches[0])).', '.$state_name.', '.$_res;  
			$out[] = array(
					'id'=>$item->id,
					'name'=>$item->name,
					'suggest_label'=>$label
				);
		}
		return $out;
	}
	
	public static function getRegionNames()
	{
		$query = "SELECT `region_str_name` as `name`, `region_str_code` as `code` FROM `".TBL_LOCATION_REGION."`";
		$result = SK_MySQL::query($query);
		$out = array();
		while ($item = $result->fetch_object()) {
			$out[$item->code] = $item->name;
		}
		return $out;
	}
	
	public static function getRegionCountries($region_id){
		$query =SK_MySQL::placeholder("SELECT `Country_str_code` AS `code`, `Country_str_name` AS `name`  FROM `".TBL_LOCATION_COUNTRY."` WHERE `region_str_code`='?'", $region_id);
		$result = SK_MySQL::query($query);
		$out = array();
		while ($item = $result->fetch_object()){
			$out[$item->code] = $item->name;
		}
		return $out;
	}
	
	public static function getCityLabelByZip( $zip )
	{
		$query = SK_MySQL::placeholder( "SELECT `lc`.`Feature_str_name` FROM `". TBL_LOCATION_ZIP. "` AS `lz` 
			LEFT JOIN `". TBL_LOCATION_CITY ."` AS `lc` ON ( `lz`.`city_id` = `lc`.`Feature_int_id` )
			WHERE `lz`.`zip` = '?'", $zip );

		return SK_MySQL::query( $query )->fetch_cell();
	}
	
	public static function CountryCityExists($country_id) {
		$query = SK_MySQL::placeholder(
			"SELECT COUNT(*) FROM `" . TBL_LOCATION_CITY . "` WHERE `Country_str_code`='?'"
		, $country_id);
		return (bool)SK_MySQL::query($query)->fetch_cell();
	}
	
	public static function findStateAbbr( $stateId )
	{
	    $query = SK_MySQL::placeholder(
	       "SELECT `Admin1_char2_code` FROM `" . TBL_LOCATION_STATE . "` WHERE `Admin1_str_code`='?'"
	    , $stateId);
	    
	    return SK_MySQL::query($query)->fetch_cell();
	}
	
	public static function getCountryByIp($ip)
	{
		$query = "SELECT `Country_str_ISO3166_2char_code` FROM `". TBL_LOCATION_COUNTRY_IP."`
		WHERE INET_ATON( \"".$ip."\" ) 	BETWEEN `startIpNum` AND `endIpNum`;";
		return SK_MySQL::query($query)->fetch_cell();	
		
	}
	
	

	public static function getDistance( $fromId, $toId )
    {
        $fromCity = app_Profile::getFieldValues($fromId, 'city_id');
        $toCity = app_Profile::getFieldValues($toId, 'city_id');

        $query = SK_MySQL::placeholder(
            "SELECT `Feature_dec_lat` AS `lat`, `Feature_dec_lon` AS `lon` FROM `" . TBL_LOCATION_CITY . "` WHERE `Feature_int_id`=?", $fromCity);
        $from = SK_MySQL::query($query)->fetch_assoc();
        if (empty($from))
        {
            return false;
        }
        
        $query = SK_MySQL::placeholder(
            "SELECT `Feature_dec_lat` AS `lat`, `Feature_dec_lon` AS `lon` FROM `" . TBL_LOCATION_CITY . "` WHERE `Feature_int_id`=?", $toCity);
        $to = SK_MySQL::query($query)->fetch_assoc();
        if (empty($to))
        {
            return false;
        }
        
        $theta = $from['lon'] - $to['lon'];
        $dist = sin(deg2rad($from['lat'])) * sin(deg2rad($to['lat'])) + cos(deg2rad($from['lat'])) * cos(deg2rad($to['lat'])) * cos(deg2rad($theta)); 
        
        $dist = acos($dist); 
        $dist = rad2deg($dist); 
        $miles = $dist * 60 * 1.1515;
        
        return (int)$miles;
    }
}
