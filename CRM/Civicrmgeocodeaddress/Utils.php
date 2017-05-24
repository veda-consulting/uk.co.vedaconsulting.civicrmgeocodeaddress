<?php

require_once 'CRM/Core/Page.php';

class CRM_Civicrmgeocodeaddress_Utils {

  static function processContacts( $processGeocode, $parseStreetAddress, $start = null, $end = null, $addressLimit = 1500) {

    // Get the geocode settings
    $settingsStr = CRM_Core_BAO_Setting::getItem('CiviCRM Geocoding Lookup', 'geocode_api_details');
    $settingsArray = unserialize($settingsStr);

    // Return if server or api key is not set
    // probably the geocode settings is not done
    if (empty($settingsArray['server']) || empty($settingsArray['api_key'])) {
      return;
    }

    // build where clause.
    //$clause = array( '( c.id = a.contact_id )' );		$clause = array();
    if ( $start ) {
      $clause[] = "( c.id >= $start )";
    }
    if ( $end ) {
      $clause[] = "( c.id <= $end )";
    }
    if ( $processGeocode ) {
      $clause[] = '( a.postal_code IS NOT NULL)';
    }
    $whereClause = implode( ' AND ', $clause );
    
    $query = "
SELECT     
           a.id as address_id,
           a.street_address,
           a.city,
           a.postal_code
FROM       civicrm_address  a
WHERE      {$whereClause}
AND NOT EXISTS (SELECT 1 FROM civicrm_address_geocoding_result d WHERE a.id = d.address_id AND UPPER(a.postal_code) = UPPER(d.postal_code))
  ORDER BY a.id
  LIMIT 0, {$addressLimit}
";

    $totalGeocoded = $totalAddresses = $totalAddressParsed = 0;
    $dao =& CRM_Core_DAO::executeQuery( $query, CRM_Core_DAO::$_nullArray );

    require_once 'CRM/Core/DAO/Address.php';
    require_once 'CRM/Core/BAO/Address.php';
    
    $unparseableContactAddress = array( );
    while ( $dao->fetch( ) ) {
      $totalAddresses++;
      $params = array( 'postal_code' => $dao->postal_code );                         
      $addressParams = array( );
        
      // process geocode.
      if ( $processGeocode ) {
        // loop through the address removing more information
        // so we can get some geocode for a partial address
        // i.e. city -> state -> country
        
        $maxTries = 5;
        do {
          if ( defined( 'THROTTLE_REQUESTS' ) &&
               THROTTLE_REQUESTS ) {
              usleep( 50000 );
          }
          
          //eval( $config->geocodeMethod . '::format( $params, true );' );
          self::parseGeoCodeXml( $params, $settingsArray);
          //print_r ($params);exit;
          array_shift( $params );
          $maxTries--;
        } while ( ( ! isset( $params['geo_code_1'] ) ) && ( $maxTries > 1 ) );
        
        if ( isset( $params['geo_code_1'] ) ) {
          $totalGeocoded++;
          $addressParams['geo_code_1'] = $params['geo_code_1'];
          $addressParams['geo_code_2'] = $params['geo_code_2'];
        }
      }
        
      // finally update address object.
      if ( !empty( $addressParams ) ) {
        $address = new CRM_Core_DAO_Address( );
        $address->id = $dao->address_id;
        $address->copyValues( $addressParams );
        $address->save( );
        $address->free( );
      }
      
      // Log the geocoding
      if ( $processGeocode ) {
        $logging_sql  = " INSERT INTO civicrm_address_geocoding_result SET ";
        $logging_sql .= " address_id   = %0, ";
        $logging_sql .= " geocoding_date = NOW(), ";
        $logging_sql .= " geocode_1    = %1, ";
        $logging_sql .= " geocode_2    = %2, ";
        $logging_sql .= " postal_code    = %3 ";

        $geo_code_1 = 'NOTFOUND';
        $geo_code_2 = 'NOTFOUND';
        if ( isset( $addressParams['geo_code_1'] ) ) {
          $geo_code_1 = $addressParams['geo_code_1'];
          $geo_code_2 = $addressParams['geo_code_2'];            
        }
         
        $logging_params = array(array($dao->address_id,   'Int'),
                                array($geo_code_1, 'String'),
                                array($geo_code_2, 'String'),
                                array($dao->postal_code, 'String'),
                               );

        $ret = CRM_Core_DAO::executeQuery($logging_sql, $logging_params);
      }        
    }

    $statusArray = array();
    
    $statusArray[] = ts( "Addresses Evaluated: $totalAddresses\n" );
    if ( $processGeocode ) {
      $statusArray[] = ts( "Addresses Geocoded : $totalGeocoded\n" );        
    }
    if ( $parseStreetAddress ) {
      $statusArray[] = ts( "Street Address Parsed : $totalAddressParsed\n" );
      if ( $unparseableContactAddress ) {
        $statusArray[] = ts( "<br />\nFollowing is the list of contacts whose address is not parsed :<br />\n");
        foreach ( $unparseableContactAddress as $contactLink ) {
            $statusArray[] = ts("%1<br />\n", array( 1 => $contactLink ) );
        }
      }
    }
    return $statusArray;
  }

  /*
   *  Function to get longitude and Latituse after passing poast code
   */
  static function parseGeoCodeXml( &$params, $settingsArray) {
    $address = array();

    if(empty($params['postal_code'])) {
      return $address;
    }

    $postcode = $params['postal_code'];

    $postcode = str_replace(' ', '' , $postcode);

    // Get the server URL
    $servertarget = $settingsArray['server'];
    $servertarget = $servertarget . "/geocodelookup/v1?key=".$settingsArray['api_key'];

    $querystring = $servertarget . "&keywords=" . urlencode($postcode);

    $filetoparse = fopen("$querystring","r") or die("Error reading JSON data.");
    $data = stream_get_contents($filetoparse);
    $simpleJSONData = json_decode($data);

    if (!empty($simpleJSONData)) {
      if ($simpleJSONData->is_error == 0 && !empty($simpleJSONData->results)) {
        $params['geo_code_1'] = (string) $simpleJSONData->results->latitude;
        $params['geo_code_2'] = (string) $simpleJSONData->results->longitude;
      }
    }
  }

  /*
  * Function to create geocode log table if not exists
  */
  static function createGeocodeLogTable() {
    if(!CRM_Core_DAO::checkTableExists('civicrm_address_geocoding_result')) {
      $logging_table_sql = "CREATE TABLE IF NOT EXISTS `civicrm_address_geocoding_result` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `address_id` int(11) NOT NULL,
        `geocoding_date` datetime NOT NULL,
        `geocode_1` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `geocode_2` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `postal_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1";
      CRM_Core_DAO::executeQuery($logging_table_sql);
    }

    // Create log table for the newly created table
    $schema = new CRM_Logging_Schema();
    $schema->fixSchemaDifferences();
  }

  /*
  * Function to create scheduled job for Civipostcode geocoding
  */
  static function createScheduledJob($is_active = 1) {

    // Chekc if the schedule job exists
    $selectSql = "SELECT * FROM civicrm_job WHERE api_entity = %1 AND api_action = %2";
    $selectParams = array(
                    '1' => array( 'civigeocode', 'String' ),
                    '2' => array( 'geocode', 'String' ),
                  );
    $selectDao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
    if (!$selectDao->fetch()) {
      // Create schedule job, if not exists
      $domainId = CRM_Core_Config::domainID();
      $query = "INSERT INTO civicrm_job SET domain_id = %1, run_frequency = %2, last_run = NULL, name = %3, description = %4,
      api_entity = %5, api_action = %6, parameters = NULL, is_active = %7";
      $params = array(
                      '1' => array( $domainId, 'Integer' ),
                      '2' => array( 'Daily', 'String' ),
                      '3' => array( 'Geocode addresses using Civipostcode.com API', 'String' ),
                      '4' => array( 'To geocode addresses is CiviCRM using Civipostcode.com Geocoding API', 'String' ),
                      '5' => array( 'civigeocode', 'String' ),
                      '6' => array( 'geocode', 'String' ),
                      '7' => array( 1, 'Integer' ),
                    );
      CRM_Core_DAO::executeQuery($query, $params);
    } else {
      // Enabled/Disable based on settings
      $updateSql = "UPDATE civicrm_job SET is_active = %3 WHERE api_entity = %1 AND api_action = %2";
      $updateParams = array(
                      '1' => array( 'civigeocode', 'String' ),
                      '2' => array( 'geocode', 'String' ),
                      '3' => array( $is_active , 'Integer' ),
                    );
      $updateDao = CRM_Core_DAO::executeQuery($updateSql, $updateParams);
    }
  }
}
