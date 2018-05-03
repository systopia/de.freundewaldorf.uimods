<?php
/*-------------------------------------------------------+
| Freunde Waldorf UI Modififications                     |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

/**
 * Derive the contact's gender from the prefix given
 */
function civicrm_api3_contact_derivegenderfromprefix($params) {
  // maximum number to be processed
  $max_count = (int) CRM_Utils_Array::value('max_count', $params, 500);

  $mapping = array(
    'Female' => array('Frau'),
    'Male'   => array('Herr'),
    );

  $genders = array(
    'Male'   => CRM_Core_OptionGroup::getValue('gender', 'männlich', 'label'),
    'Female' => CRM_Core_OptionGroup::getValue('gender', 'weiblich', 'label')
    );

  if (empty($genders['Male']) || empty($genders['Female'])) {
    return civicrm_api3_create_error("Gender 'männlich' or 'weiblich' could not be resolved.");
  }

  $prefix2gender = array();
  foreach ($mapping as $gender_name => $prefixes) {
    $gender_id = $genders[$gender_name];
    foreach ($prefixes as $prefix_label) {
      $prefix_id = CRM_Core_OptionGroup::getValue('individual_prefix', $prefix_label, 'label');
      if (!empty($prefix_id)) {
        $prefix2gender[$prefix_id] = $gender_id;
      }
    }
  }

  if (empty($prefix2gender)) {
    return civicrm_api3_create_error("None of the prefixes could be resolved.");
  }

  // Restrict the affected contacts query to the given contact IDs.
  if (!empty($params['contact_ids'])) {
    $contact_ids = array_filter(
      array_map(
        'trim',
        explode(',', $params['contact_ids'])
      ),
      'is_numeric'
    );
    $contact_ids_clause_sql = 'id IN(' . implode(',', $contact_ids) . ')';
  }

  // create a query to find the contacts affected
  $gender_clauses = array();
  foreach ($prefix2gender as $prefix_id => $gender_id) {
    $gender_clauses[] = "prefix_id = {$prefix_id} AND (gender_id != {$gender_id} OR gender_id IS NULL)";
  }
  $gender_clause_sql = '((' . implode(') OR (', $gender_clauses) . '))';

  $prefix_id_list = implode(',', array_keys($prefix2gender));
  $contact_query = "
  SELECT  id, prefix_id
  FROM    civicrm_contact
  WHERE   is_deleted = 0
    AND   contact_type = 'Individual'
    AND {$gender_clause_sql}
    " . (!empty($contact_ids_clause_sql) ? 'AND ' . $contact_ids_clause_sql : '') . "
    LIMIT $max_count
  ";
  $affected_contact = CRM_Core_DAO::executeQuery($contact_query);

  // now collect every one of them into a set of changes
  $changes = array();
  while ($affected_contact->fetch()) {
    if (isset($prefix2gender[$affected_contact->prefix_id])) {
      $changes[] = array(
        'id'        => $affected_contact->id,
        'gender_id' => $prefix2gender[$affected_contact->prefix_id],
      );
    }
  }

  // now execute
  foreach ($changes as $change) {
    civicrm_api3('Contact', 'create', $change);
  }

  return civicrm_api3_create_success(count($changes));
}

/**
 * API3 action specs
 */
function _civicrm_api3_contact_derivegenderfromprefix_spec(&$params) {
  $params['max_count']['api.required'] = 0;
  $params['contact_ids'] = array(
    'name'         => 'contact_ids',
    'title'        => 'Contact IDs',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'A list of contact IDs to act on.',
  );
}
