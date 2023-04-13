<?php

require_once 'uimods.civix.php';
use CRM_Uimods_ExtensionUtil as E;

/**
 * Adjust the API permissions for activities, see FW-9755
 */
function uimods_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  $permissions['activity']['getcount'] = ['view all activities'];
  $permissions['activity']['get']      = ['view all activities'];
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function uimods_civicrm_config(&$config) {
  _uimods_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function uimods_civicrm_install() {
  _uimods_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function uimods_civicrm_enable() {
  _uimods_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_pre().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_pre
 */
function uimods_civicrm_pre($op, $objectName, $id, &$params) {
  if ($objectName == 'Individual') {
    switch ($op) {
      case 'create':
      case 'edit':
        CRM_Uimods_GenderPrefix::updateGenderOrPrefixParams($id, $params);
        break;
    }
  }
}

/**
 * Implements hook_civicrm_summary().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_summary
 */
function uimods_civicrm_summary($contactID, &$content, &$contentPlacement = CRM_Utils_Hook::SUMMARY_BELOW) {
  // Add JavaScript to the contact summary page.
  $script = file_get_contents(__DIR__ . '/js/contact.js');
  CRM_Core_Region::instance('page-body')->add(array(
    'script' => $script,
  ));

  // Add the contact ID as a JavaScript variable on the contact summary page.
  CRM_Core_Resources::singleton()->addVars('uimods', array('contactId' => $contactID));

}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function uimods_civicrm_navigationMenu(&$menu) {
  _uimods_civix_insert_navigation_menu($menu, NULL, array(
    'label' => E::ts('The Page'),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _uimods_civix_navigationMenu($menu);
} // */
