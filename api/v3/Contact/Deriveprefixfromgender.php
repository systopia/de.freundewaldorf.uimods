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

declare(strict_types = 1);

/**
 * Derive the contact's prefix from the gender given
 *
 * @param array<string,string> $params
 * @return array<string,mixed>
 */
function civicrm_api3_contact_deriveprefixfromgender($params): array {
  // maximum number to be processed
  $max_count = $params['max_count'] ?? 500;

  try {
    $gender2prefix = CRM_Uimods_GenderPrefix::getPrefixMapping();
    $and_clause_sql_contact_ids = '';

    // Restrict the affected contacts query to the given contact IDs.
    if (isset($params['contact_ids'])) {
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
      $and_clause_sql_contact_ids = 'AND ' . $contact_ids_clause_sql;
    }

    // create a query to find the contacts affected
    $prefix_clauses = [];
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
    " . $and_clause_sql_contact_ids . "
    LIMIT $max_count
  ";
    /** @var CRM_Core_DAO $affected_contact  */
    $affected_contact = CRM_Core_DAO::executeQuery($contact_query);

    // now collect every one of them into a set of changes
    $changes = [];
    while ($affected_contact->fetch()) {
      if (isset($gender2prefix[$affected_contact->gender_id])) {
        $changes[] = [
          'id'        => $affected_contact->id,
          'prefix_id' => $gender2prefix[$affected_contact->gender_id],
        ];
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
 *
 * @param array<string, array<string,mixed>> $params
 */
function _civicrm_api3_contact_deriveprefixfromgender_spec(array &$params): void {
  $params['max_count']['api.required'] = 0;
  $params['contact_ids'] = [
    'name'         => 'contact_ids',
    'title'        => 'Contact IDs',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'A list of contact IDs to act on.',
  ];
}
