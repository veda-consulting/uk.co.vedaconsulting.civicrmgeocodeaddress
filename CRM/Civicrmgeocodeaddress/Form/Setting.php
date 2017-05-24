<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Civicrmgeocodeaddress_Form_Setting extends CRM_Core_Form {
  
  function buildQuickForm() {

    $settingsStr = CRM_Core_BAO_Setting::getItem('CiviCRM Geocoding Lookup', 'geocode_api_details');

    $settingsArray = unserialize($settingsStr);

    // Enable geocode, if civipostcode.com is used
    $this->addElement(
      'checkbox', 
      'is_geocoding_enabled', 
      ts('Enable Geocoding?')
    );
    
    // Geocoding loookup Provider
    $this->add(
      'select', // field type
      'provider', // field name
      ts('Provider'), // field label
      $this->getProviderOptions(), // list of options
      false // is required
    );

    // Server URL
    $this->addElement(
      'text',
      'server',
      ts('Server URL'),
      array('size' => 50),
      true
    );

    // API Key
    $this->addElement(
      'text',
      'api_key',
      ts('API Key'),
      array('size' => 50),
      false
    );

    // Update city
    /*$this->addElement(
      'checkbox', 
      'update_city', 
      ts('Update City?')
    );

    // Update county
    $this->addElement(
      'checkbox', 
      'update_county', 
      ts('Update County?')
    );*/

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    $this->setDefaults($settingsArray);

    $this->addFormRule( array( 'CRM_Civicrmgeocodeaddress_Form_Setting', 'formRule' ) );

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  static function formRule( $values ){

    $errors = array();

    // Check all mandatory values are entered for Civipostcode
    if ($values['provider'] == 'civipostcode') {
      if (empty($values['api_key'])) {
        $errors['api_key'] = ts( "API Key is mandatory." );
      }
    }

    return $errors;
  }

  function getProviderOptions() {
    return $GLOBALS["geocoding_providers"];
  }

  function postProcess() {
    $values = $this->exportValues();

    /*$updateCity = 0;
    if (isset($values['update_city']) && $values['update_city'] == 1) {
      $updateCity = 1;
    }

    $updateCounty = 0;
    if (isset($values['update_county']) && $values['update_county'] == 1) {
      $updateCounty = 1;
    }*/

    $settingsArray = array();

    if (isset($values['is_geocoding_enabled'])) {
      $settingsArray['provider'] = $values['provider'];
      $settingsArray['server'] = $values['server'];
      $settingsArray['api_key'] = $values['api_key'];
      $settingsArray['is_geocoding_enabled'] = $values['is_geocoding_enabled'];
      //$settingsArray['update_city'] = $updateCity;
      //$settingsArray['update_county'] = $updateCounty;

      // Create geocoding log table
      CRM_Civicrmgeocodeaddress_Utils::createGeocodeLogTable();
      
      // Create scheduled job
      CRM_Civicrmgeocodeaddress_Utils::createScheduledJob($is_active = 1);
    } else {
      // Disable scheduled job
      CRM_Civicrmgeocodeaddress_Utils::createScheduledJob($is_active = 0);
    }

    $settingsStr = serialize($settingsArray);

    CRM_Core_BAO_Setting::setItem($settingsStr,
      'CiviCRM Geocoding Lookup',
      'geocode_api_details'
    );

    $message = "Settings saved.";
    CRM_Core_Session::setStatus($message, 'Geocoding Settings', 'success');
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}
