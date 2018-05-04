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
 * Derive the contact's prefix from the gender given
 */
function civicrm_api3_contact_deriveprefixfromgender($params) {
  // maximum number to be processed
  $max_count = (int) CRM_Utils_Array::value('max_count', $params, 500);

  try {
    $gender2prefix = CRM_Uimods_GenderPrefix::getPrefixMapping();

    // Restrict the affected contacts query to the given contact IDs.
    if (!empty($params['contact_ids'])) {
      if (!is_array($params['contact_ids'])) {
        $contact_ids = explode(',', $params['contact_ids']);
      }
      else {
        $contact_ids = $params['contact_ids'];
      }
      $contact_ids = array_filter(
        array_map(
          'trim',
          $contact_ids
        ),
        'is_numeric'
      );
      $contact_ids_clause_sql = 'id IN(' . implode(',', $contact_ids) . ')';
    }

    // create a query to find the contacts affected
    $prefix_clauses = array();
    foreach ($gender2prefix as $gender_id => $prefix_id) {
      $prefix_clauses[] = "gender_id = {$gender_id} AND (prefix_id != {$prefix_id} OR prefix_id IS NULL)";
    }
    $prefix_clause_sql = '((' . implode(') OR (', $prefix_clauses) . '))';

    $gender_id_list = implode(',', array_keys($gender2prefix));
    $contact_query = "
  SELECT  id, gender_id
  FROM    civicrm_contact
  WHERE   is_deleted = 0
    AND   contact_type = 'Individual'
    AND {$prefix_clause_sql}
    " . (!empty($contact_ids_clause_sql) ? 'AND ' . $contact_ids_clause_sql : '') . "
    LIMIT $max_count
  ";
    $affected_contact = CRM_Core_DAO::executeQuery($contact_query);

    // now collect every one of them into a set of changes
    $changes = array();
    while ($affected_contact->fetch()) {
      if (isset($gender2prefix[$affected_contact->gender_id])) {
        $changes[] = array(
          'id'        => $affected_contact->id,
          'prefix_id' => $gender2prefix[$affected_contact->gender_id],
        );
      }
    }

    // now execute
    foreach ($changes as $change) {
      civicrm_api3('Contact', 'create', $change);
    }

    return civicrm_api3_create_success(count($changes));
  }
  catch (Exception $exception) {
    return civicrm_api3_create_error($exception->getMessage());
  }
}

/**
 * API3 action specs
 */
function _civicrm_api3_contact_deriveprefixfromgender_spec(&$params) {
  $params['max_count']['api.required'] = 0;
  $params['contact_ids'] = array(
    'name'         => 'contact_ids',
    'title'        => 'Contact IDs',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'A list of contact IDs to act on.',
  );
}
