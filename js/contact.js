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
 * @file
 *
 * JavaScript to be executed on the contact summary page.
 */

(function($) {
  $(document).on('crmFormSuccess', function(event) {
    switch ($(event.target).attr('id')) {
      case 'crm-contactname-content':
        // When updating fields in the contact name block, reload the
        // demographics block, in order to reflect the updated gender when the
        // prefix changes.
        var $demographicsBlock = $('#crm-demographic-content');
        var demographicsData = $demographicsBlock.data('edit-params');
        demographicsData.snippet = demographicsData.reset = 1;
        demographicsData.class_name = demographicsData.class_name.replace('Form', 'Page');
        demographicsData.type = 'page';
        $demographicsBlock.closest('.crm-summary-block').load(CRM.url('civicrm/ajax/inline', demographicsData), function() {$(this).trigger('crmLoad');});
        break;

      case 'crm-demographic-content':
        // When updating fields in the demographics block, reload the contact
        // name block, in order to reflect the updated prefix when the gender
        // changes.
        var $nameBlock = $('#crm-contactname-content');
        var nameData = $nameBlock.data('edit-params');
        nameData.snippet = nameData.reset = 1;
        nameData.class_name = nameData.class_name.replace('Form', 'Page');
        nameData.type = 'page';
        $nameBlock.closest('.crm-summary-block').load(CRM.url('civicrm/ajax/inline', nameData), function() {$(this).trigger('crmLoad');});
        break;
    }
  });
})(cj);
