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
 * Class CRM_Uimods_GenderPrefix
 *
 * A utility class for updating gender or prefix when each other changes.
 */
class CRM_Uimods_GenderPrefix {

  /**
   * @return array
   * @throws \Exception
   */
  public static function getGenderMapping() {
    $mapping = array(
      'Female' => array('Frau'),
      'Male'   => array('Herr'),
    );
    $genders = array(
      'Male'   => CRM_Core_OptionGroup::getValue('gender', 'männlich', 'label'),
      'Female' => CRM_Core_OptionGroup::getValue('gender', 'weiblich', 'label')
    );
    if (empty($genders['Male']) || empty($genders['Female'])) {
      throw new Exception("Gender 'männlich' or 'weiblich' could not be resolved.");
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
      throw new Exception("None of the prefixes could be resolved.");
    }
    return $prefix2gender;
  }

  /**
   * @return array
   * @throws \Exception
   */
  public static function getPrefixMapping() {
    $mapping = array(
      'Frau' => array('weiblich'),
      'Herr'   => array('männlich'),
    );
    $prefixes = array(
      'Herr'   => CRM_Core_OptionGroup::getValue('individual_prefix', 'Herr', 'label'),
      'Frau' => CRM_Core_OptionGroup::getValue('individual_prefix', 'Frau', 'label')
    );
    if (empty($prefixes['Herr']) || empty($prefixes['Frau'])) {
      throw new Exception("Prefix 'Herr' or 'Frau' could not be resolved.");
    }
    $gender2prefix = array();
    foreach ($mapping as $prefix_name => $genders) {
      $prefix_id = $prefixes[$prefix_name];
      foreach ($genders as $gender_label) {
        $gender_id = CRM_Core_OptionGroup::getValue('gender', $gender_label, 'label');
        if (!empty($gender_id)) {
          $gender2prefix[$gender_id] = $prefix_id;
        }
      }
    }

    if (empty($gender2prefix)) {
      throw new Exception("None of the genders could be resolved.");
    }
    return $gender2prefix;
  }

  /**
   * @param $prefix_id
   *
   * @return mixed
   * @throws \Exception
   */
  public static function deriveGenderFromPrefix($prefix_id) {
    $genders = static::getGenderMapping();
    if (!isset($genders[$prefix_id])) {
      throw new Exception('Prefix could not be resolved.');
    }
    return $genders[$prefix_id];
  }

  /**
   * @param $gender_id
   *
   * @return mixed
   * @throws \Exception
   */
  public static function derivePrefixFromGender($gender_id) {
    $prefixes = static::getPrefixMapping();
    if (!isset($prefixes[$gender_id])) {
      throw new Exception('Gender could not be resolved.');
    }
    return $prefixes[$gender_id];
  }

  /**
   * Updates the parameters array when either gender or prefix changed for a
   * given contact.
   *
   * @param $contact_id
   * @param $params
   */
  public static function updateGenderOrPrefixParams($contact_id, &$params) {
    try {
      if ($contact_id) {
        // Compare with old values.
        $old_contact = civicrm_api3('Contact', 'getsingle', array(
          'id' => $contact_id,
          'return' => array('gender_id', 'prefix_id')
        ));
        if (isset($params['gender_id']) && $old_contact['gender_id'] != $params['gender_id']) {
          $params['prefix_id'] = static::derivePrefixFromGender($params['gender_id']);
        }
        elseif (isset($params['prefix_id']) && $old_contact['prefix_id'] != $params['prefix_id']) {
          $params['gender_id'] = static::deriveGenderFromPrefix($params['prefix_id']);
        }
      }
      else {
        // Compare params only, gender preceding.
        if (!empty($params['gender_id'])) {
          $params['prefix_id'] = static::derivePrefixFromGender($params['gender_id']);
        }
        elseif (!empty($params['prefix_id'])) {
          $params['gender_id'] = static::deriveGenderFromPrefix($params['prefix_id']);
        }
      }

    }
    catch (Exception $exception) {
      // If gender or prefix could not be determined, leave them alone.
    }
  }

}
