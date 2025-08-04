<?php


declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
require_once 'uimods.civix.php';
use CRM_Uimods_ExtensionUtil as E;

/**
 * Adjust the API permissions for activities, see FW-9755
 *
 * @param array<string,string> $params
 * @param array<string,array<string,array<string>>> $permissions
 */
function uimods_civicrm_alterAPIPermissions(string $entity, string $action, array &$params, array &$permissions): void {
  $permissions['activity']['getcount'] = ['view all activities'];
  $permissions['activity']['get']      = ['view all activities'];
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function uimods_civicrm_config(CRM_Core_Config &$config): void {
  _uimods_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function uimods_civicrm_install(): void {
  _uimods_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function uimods_civicrm_enable(): void {
  _uimods_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_pre().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_pre
 * @param array<string,string> $params
 *
 *
 */
 // phpcs:ignore
function uimods_civicrm_pre(string $op, string $objectName, ?int $id, array &$params): void {
  if ('Individual' === $objectName) {
    switch ($op) {
      case 'create':
      case 'edit':
        if (NULL === $id) {
          return;
        }
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
function uimods_civicrm_summary(int $contactID, mixed &$content,
  int &$contentPlacement = CRM_Utils_Hook::SUMMARY_BELOW): void {
  // Add JavaScript to the contact summary page.
  $script = file_get_contents(__DIR__ . '/js/contact.js');
  CRM_Core_Region::instance('page-body')->add([
    'script' => $script,
  ]);

  // Add the contact ID as a JavaScript variable on the contact summary page.
  CRM_Core_Resources::singleton()->addVars('uimods', ['contactId' => $contactID]);
}
