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
 * Class CRM_Uimods_GenderPrefix
 *
 * A utility class for updating gender or prefix when each other changes.
 */
class CRM_Uimods_GenderPrefix {

  /**
   * will be invoked when gender type changes, then prefix name will be adjusted accordingly´
   *
   * @return array<string,string>
   * @throws \RuntimeException
   */
  public static function getGenderMapping(): array {
    $genderPrefixMapping = [
      'Female' => ['Frau', 'Ms.', 'Mrs.', 'Señora'],
      'Male'   => ['Herr', 'Mr.', 'Señor'],
    ];

    $allowedLabels = [
      'Female' => ['Female', 'weiblich'],
      'Male'   => ['Male', 'männlich'],
    ];

    /** var array<string,string> $genders */
    $genders = [];
    foreach ($allowedLabels as $genderName => $genderOptions) {
      foreach ($genderOptions as $genderOption) {
        $genderKey = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', 'gender_id', $genderOption);
        if (NULL !== $genderKey && '' !== (string) $genderKey) {
          $genders[$genderName] = (string) $genderKey;
          break;
        }
      }
    }

    if (!array_key_exists('Male', $genders) || !array_key_exists('Female', $genders)) {
      throw new \RuntimeException("Gender 'männlich' or 'weiblich' could not be resolved.");
    }

    $prefix2gender = [];
    foreach ($genderPrefixMapping as $genderName => $prefixes) {
      $genderId = $genders[$genderName];
      foreach ($prefixes as $prefixLabel) {
        /** var bool|null|string|int $prefixId */
        $prefixId = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', 'prefix_id', $prefixLabel);
        if (NULL !== $prefixId && '' !== (string) $prefixId) {
          $prefix2gender[(string) $prefixId] = $genderId;
        }
      }
    }

    if (0 === count($prefix2gender)) {
      throw new \RuntimeException('None of the prefixes could be resolved.');
    }
    return $prefix2gender;
  }

  /**
   * @return array<string,string>
   * @throws \RuntimeException
   */
  public static function getPrefixMapping(): array {
    $prefixGenderMapping = [
      'Frau'   => ['Female', 'männlich'],
      'Herr' => ['Male', 'weiblich'],
    ];

    $allowedLabels = [
      'Frau' => ['Frau', 'Ms.', 'Mrs.', 'Señora'],
      'Herr'   => ['Herr', 'Mr.', 'Señor'],
    ];

    /** var array<string,string> $prefixes */
    $prefixes = [];
    foreach ($allowedLabels as $prefixName => $prefixOptions) {
      foreach ($prefixOptions as $prefixOption) {
        /** var bool|null|string|int $prefixId */
        $prefixKey = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', 'prefix_id', $prefixOption);
        if (NULL !== $prefixKey && '' !== (string) $prefixKey) {
          $prefixes[$prefixName] = (string) $prefixKey;
          break;
        }
      }
    }

    if (!array_key_exists('Herr', $prefixes) || !array_key_exists('Frau', $prefixes)) {
      throw new \RuntimeException("Prefix 'Herr' or 'Frau' could not be resolved.");
    }

    $gender2prefix = [];
    foreach ($prefixGenderMapping as $prefixName => $genders) {
      $prefixId = $prefixes[$prefixName];
      foreach ($genders as $genderLabel) {
        $genderId = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', 'gender_id', $genderLabel);
        if (NULL !== $genderId && '' !== (string) $genderId) {
          $gender2prefix[(string) $genderId] = $prefixId;
        }
      }
    }

    if (0 === count($gender2prefix)) {
      throw new \RuntimeException('None of the genders could be resolved.');
    }
    return $gender2prefix;
  }

  /**
   * @param string $prefixId
   *
   * @return string
   * @throws \RuntimeException
   */
  public static function deriveGenderFromPrefix($prefixId): string {
    $genders = static::getGenderMapping();
    if (!isset($genders[$prefixId])) {
      throw new \RuntimeException('Prefix-Id ' . $prefixId . ' could not be resolved.');
    }
    return $genders[$prefixId];
  }

  /**
   * @param string $genderId
   *
   * @return string
   * @throws \RuntimeException
   */
  public static function derivePrefixFromGender($genderId): string {
    $prefixes = static::getPrefixMapping();
    if (!isset($prefixes[$genderId])) {
      throw new \RuntimeException('Gender-Id ' . $genderId . ' could not be resolved.');
    }
    return $prefixes[$genderId];
  }

  /**
   * Updates the parameters array when either gender or prefix changed for a
   * given contact.
   *
   * @param int $contact_id
   * @param array<string,string> $params
   */
  public static function updateGenderOrPrefixParams(int $contact_id, array &$params): void {
    try {
      // Compare with old values.
      $old_contact = civicrm_api3('Contact', 'getsingle', [
        'id' => $contact_id,
        'return' => ['gender_id', 'prefix_id'],
      ]);

      if (is_array($old_contact)) {
        if (isset($params['gender_id']) && $old_contact['gender_id'] !== $params['gender_id']) {
          $params['prefix_id'] = static::derivePrefixFromGender($params['gender_id']);
        }
        elseif (isset($params['prefix_id']) && $old_contact['prefix_id'] !== $params['prefix_id']) {
          $params['gender_id'] = static::deriveGenderFromPrefix($params['prefix_id']);
        }
      }
      else {
        // Compare params only, gender preceding.
        $genderId = $params['gender_id'];
        $prefixId = $params['prefix_id'];
        if ('' !== $genderId) {
          $params['prefix_id'] = static::derivePrefixFromGender($genderId);
        }
        elseif ('' !== $prefixId) {
          $params['gender_id'] = static::deriveGenderFromPrefix($prefixId);
        }
      }
    }
    catch (\RuntimeException $exception) {
      // @ignoreException
      // If gender or prefix could not be determined, leave them alone.
    }
  }

}
