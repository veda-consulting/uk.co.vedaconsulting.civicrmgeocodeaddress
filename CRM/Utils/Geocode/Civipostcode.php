<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.6                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the CiviCRM Licensing Exception along                  |
  | with this program; if not, contact CiviCRM LLC                     |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * Class that uses civipostcode geocoder
 */
class CRM_Utils_Geocode_Civipostcode {

  /**
   * Server to retrieve the lat/long
   *
   * @var string
   */
  static protected $_server = 'http://civipostcode.com';

  /**
   * Uri of service.
   *
   * @var string
   */
  static protected $_uri = '/geocodelookup/v1?key=';

  /**
   * Function that takes an address object and gets the latitude / longitude for this
   * address. Note that at a later stage, we could make this function also clean up
   * the address into a more valid format
   *
   * @param array $values
   * @param bool $stateName
   *
   * @return bool
   *   true if we modified the address, false otherwise
   */
  public static function format(&$values, $stateName = FALSE) {
    // we need a valid country, else we ignore
    if (empty($values['country'])) {
      return FALSE;
    }

    $config = CRM_Core_Config::singleton();

    $add = '';

    if (!empty($values['postal_code'])) {

      $postcode = $values['postal_code'];
      
      $postcode = str_replace(' ', '' , $postcode);

    }

    // Get the server URL

    $settingsStr = CRM_Core_BAO_Setting::getItem('CiviCRM Geocoding Lookup', 'geocode_api_details');
    $settingsArray = unserialize($settingsStr);

    $servertarget = $settingsArray['server'];
    $servertarget = $servertarget . "/geocodelookup/v1?key=".$settingsArray['api_key'];

    $querystring = $servertarget . "&keywords=" . urlencode($postcode);

    $filetoparse = fopen("$querystring","r") or die("Error reading JSON data.");

    $data = stream_get_contents($filetoparse);
    $simpleJSONData = json_decode($data);
    if (!empty($simpleJSONData)) {
      if ($simpleJSONData->is_error == 0 && !empty($simpleJSONData->results)) {
        $values['geo_code_2'] = (string) $simpleJSONData->results->latitude;
        $values['geo_code_1'] = (string) $simpleJSONData->results->longitude;
        return TRUE;
      }
    }

    // reset the geo code values if we did not get any good values
    $values['geo_code_1'] = $values['geo_code_2'] = 'null';
    return FALSE;
  }

}
