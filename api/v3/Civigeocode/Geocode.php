<?php

/**
 * Civigeocode.Geocode API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_civigeocode_Geocode_spec(&$spec) {
  //$spec['magicword']['api.required'] = 1;
}

/**
 * Civigeocode.Geocode API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_civigeocode_Geocode($params) {

  $processGeocode = true;
  $parseStreetAddress = false;

  // Check if Civipostcode Geocoding API is enabled as geocoding provider
  $settingsStr = CRM_Core_BAO_Setting::getItem('CiviCRM Geocoding Lookup', 'geocode_api_details');
  $settingsArray = unserialize($settingsStr);
  if (isset($settingsArray['is_geocoding_enabled']) && $settingsArray['is_geocoding_enabled'] == 1) {
    $returnValues = CRM_Civicrmgeocodeaddress_Utils::processContacts($processGeocode, $parseStreetAddress);  
  } else {
    $returnValues = array("Civipostcode API is not enabled as Geocoding provider.");
  }
  return civicrm_api3_create_success($returnValues, $params, 'Civigeocode', 'Geocode');
}