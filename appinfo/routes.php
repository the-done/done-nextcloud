<?php

return [
    'routes' => [
        //////// USERS ////////
        [
            'name' => 'users#getUsersData',
            'url'  => '/ajax/getUsersData',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#getUsersForProject',
            'url'  => '/ajax/getUsersForProject',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#getUserPublicData',
            'url'  => '/ajax/getUserPublicData',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#getFreeNextcloudUsers',
            'url'  => '/ajax/getFreeNextcloudUsers',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#saveUser',
            'url'  => '/ajax/saveUser',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#deleteUser',
            'url'  => '/ajax/deleteUser',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#getStatisticsByUser',
            'url'  => '/ajax/getStatisticsByUser',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#getSimpleUsers',
            'url'  => '/ajax/getSimpleUsers',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#addUserToDirection',
            'url'  => '/ajax/addUserToDirection',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#editUserInDirection',
            'url'  => '/ajax/editUserInDirection',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#getUserInDirection',
            'url'  => '/ajax/getUserInDirection',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#deleteUserFromDirection',
            'url'  => '/ajax/deleteUserFromDirection',
            'verb' => 'POST',
        ],
        [
            'name' => 'users#getUsersTableData',
            'url'  => '/ajax/getUsersTableData',
            'verb' => 'POST',
        ],

        //////// HUMAN RESOURCES ////////

        [
            'name' => 'human_resources#savePersonnel',
            'url'  => '/ajax/savePersonnel',
            'verb' => 'POST',
        ],
        [
            'name' => 'human_resources#getPersonnel',
            'url'  => '/ajax/getPersonnel',
            'verb' => 'POST',
        ],
        [
            'name' => 'human_resources#deletePersonnel',
            'url'  => '/ajax/deletePersonnel',
            'verb' => 'POST',
        ],

        //////// TEAMS ////////

        [
            'name' => 'teams#getTeamsTableData',
            'url'  => '/ajax/getTeamsTableData',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#saveTeam',
            'url'  => '/ajax/saveTeam',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#deleteTeam',
            'url'  => '/ajax/deleteTeam',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#getTeams',
            'url'  => '/ajax/getTeams',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#getTeamsPublicData',
            'url'  => '/ajax/getTeamsPublicData',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#getEmployeesInTeams',
            'url'  => '/ajax/getEmployeesInTeams',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#addEmployeeToTeam',
            'url'  => '/ajax/addEmployeeToTeam',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#editEmployeeToTeam',
            'url'  => '/ajax/editEmployeeToTeam',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#removeEmployeeFromTeam',
            'url'  => '/ajax/removeEmployeeFromTeam',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#getTeamsInDirections',
            'url'  => '/ajax/getTeamsInDirections',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#addTeamToDirection',
            'url'  => '/ajax/addTeamToDirection',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#removeTeamFromDirection',
            'url'  => '/ajax/removeTeamFromDirection',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#getTeamsInProjects',
            'url'  => '/ajax/getTeamsInProjects',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#addTeamToProject',
            'url'  => '/ajax/addTeamToProject',
            'verb' => 'POST',
        ],
        [
            'name' => 'teams#removeTeamFromProject',
            'url'  => '/ajax/removeTeamFromProject',
            'verb' => 'POST',
        ],

        //////// ADMIN ////////

        [
            'name' => 'admin#getNextcloudUsersData',
            'url'  => '/ajax/getNextcloudUsersData',
            'verb' => 'POST',
        ],
        [
            'name' => 'admin#deleteUsersRoles',
            'url'  => '/ajax/deleteUsersRoles',
            'verb' => 'POST',
        ],
        [
            'name' => 'admin#saveGlobalRoleToUser',
            'url'  => '/ajax/saveGlobalRoleToUser',
            'verb' => 'POST',
        ],
        [
            'name' => 'admin#deleteGlobalRoleFromUser',
            'url'  => '/ajax/deleteGlobalRoleFromUser',
            'verb' => 'POST',
        ],
        [
            'name' => 'admin#getDataToViewEntity',
            'url'  => '/ajax/getDataToViewEntity',
            'verb' => 'POST',
        ],

        //////// MODULES CONTROLLER ////////

        [
            'name' => 'modules#module',
            'url' => '/ajax/module/{module}',
            'verb' => 'POST',
        ],
        [
            'name' => 'modules#getAvailableModules',
            'url' => '/ajax/getAvailableModules',
            'verb' => 'POST',
        ],

        //////// FILE CONTROLLER ////////

        [
            'name' => 'file#getEntityFile',
            'url'  => '/file/{entityId}/{fileType}/{fileName}',
            'verb' => 'GET',
        ],

        ////// ENTITY ////////////////
        [
            'name' => 'entity#getDataToViewEntity',
            'url'  => '/ajax/getDataToViewEntity',
            'verb' => 'POST',
        ],
        [
            'name' => 'entity#saveEntityImage',
            'url'  => '/ajax/saveEntityImage',
            'verb' => 'POST',
        ],
        [
            'name' => 'entity#saveFieldsOrdering',
            'url'  => '/ajax/saveFieldsOrdering',
            'verb' => 'POST',
        ],
        [
            'name' => 'entity#resetToDefaultOrdering',
            'url'  => '/ajax/resetToDefaultOrdering',
            'verb' => 'POST',
        ],
        [
            'name' => 'entity#getFieldsOrdering',
            'url'  => '/ajax/getFieldsOrdering',
            'verb' => 'POST',
        ],
        [
            'name' => 'entity#saveEntityColor',
            'url'  => '/ajax/saveEntityColor',
            'verb' => 'POST',
        ],
        [
            'name' => 'entity#exportToExcel',
            'url'  => '/ajax/exportToExcel',
            'verb' => 'POST',
        ],

        //////// DICTIONARIES ////////

        [
            'name' => 'dictionaries#getDictionaryData',
            'url'  => '/ajax/getDictionaryData',
            'verb' => 'GET',
        ],
        [
            'name' => 'dictionaries#saveDict',
            'url'  => '/ajax/saveDict',
            'verb' => 'POST',
        ],
        [
            'name' => 'dictionaries#getDictionaryItemData',
            'url'  => '/ajax/getDictionaryItemData',
            'verb' => 'POST',
        ],
        [
            'name' => 'dictionaries#deleteDictItem',
            'url'  => '/ajax/deleteDictItem',
            'verb' => 'POST',
        ],
        [
            'name' => 'dictionaries#getGlobalRoles',
            'url'  => '/ajax/getGlobalRoles',
            'verb' => 'POST',
        ],
        [
            'name' => 'dictionaries#getUserGlobalRoles',
            'url'  => '/ajax/getUserGlobalRoles',
            'verb' => 'POST',
        ],
        [
            'name' => 'dictionaries#getUsersByGlobalRole',
            'url'  => '/ajax/getUsersByGlobalRole',
            'verb' => 'POST',
        ],
        [
            'name' => 'dictionaries#getNextSortInDict',
            'url'  => '/ajax/getNextSortInDict',
            'verb' => 'POST',
        ],

        //////// COMMON ////////

        [
            'name' => 'common#getUserStatistics',
            'url'  => '/ajax/getUserStatistics',
            'verb' => 'POST',
        ],
        [
            'name' => 'common#saveUserTimeInfo',
            'url'  => '/ajax/saveUserTimeInfo',
            'verb' => 'POST',
        ],
        [
            'name' => 'common#editUserTimeInfo',
            'url'  => '/ajax/editUserTimeInfo',
            'verb' => 'POST',
        ],
        [
            'name' => 'common#editReportSort',
            'url'  => '/ajax/editReportSort',
            'verb' => 'POST',
        ],
        [
            'name' => 'common#editReportSortMultiple',
            'url'  => '/ajax/editReportSortMultiple',
            'verb' => 'POST',
        ],
        [
            'name' => 'common#deleteUserTimeInfo',
            'url'  => '/ajax/deleteUserTimeInfo',
            'verb' => 'POST',
        ],
        [
            'name' => 'common#getUserTimeInfo',
            'url'  => '/ajax/getUserTimeInfo',
            'verb' => 'POST',
        ],
        [
            'name' => 'common#getPermissions',
            'url'  => '/ajax/getPermissions',
            'verb' => 'POST',
        ],
        [
            'name' => 'common#sendReportToNextStatus',
            'url'  => '/ajax/sendReportToNextStatus',
            'verb' => 'POST',
        ],
        [
            'name' => 'common#getUserRolesInProject',
            'url'  => '/ajax/getUserRolesInProject',
            'verb' => 'POST',
        ],
        [
            'name' => 'common#getUserProjects',
            'url'  => '/ajax/getUserProjects',
            'verb' => 'POST',
        ],
        [
            'name' => 'common#getUserProjectsForReport',
            'url'  => '/ajax/getUserProjectsForReport',
            'verb' => 'POST',
        ],

        // Time tracking main page
        [
            'name'    => 'common#index',
            'url'     => '/',
            'verb'    => 'GET',
            'postfix' => 'report.createReport',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/time-tracking/{id}/',
            'verb'    => 'GET',
            'postfix' => 'report.editReport',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/{taskLink}/',
            'verb'    => 'GET',
            'postfix' => 'report.viewTask',
        ],

        // Statistics page
        [
            'name'    => 'common#index',
            'url'     => '/statistics/',
            'verb'    => 'GET',
            'postfix' => 'statistics',
        ],

        // Reports page
        [
            'name'    => 'common#index',
            'url'     => '/reports/',
            'verb'    => 'GET',
            'postfix' => 'reports',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/reports/common/',
            'verb'    => 'GET',
            'postfix' => 'reports.common',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/reports/projects/',
            'verb'    => 'GET',
            'postfix' => 'reports.projects',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/reports/staff/',
            'verb'    => 'GET',
            'postfix' => 'reports.staff',
        ],

        // Finance pages
        [
            'name'    => 'common#index',
            'url'     => '/finances/',
            'verb'    => 'GET',
            'postfix' => 'finances',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/finances/payments/',
            'verb'    => 'GET',
            'postfix' => 'finances.payments',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/finances/payments/new',
            'verb'    => 'GET',
            'postfix' => 'finances.payment.new',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/finances/payments/{slug}/',
            'verb'    => 'GET',
            'postfix' => 'finances.payment.edit',
        ],

        // Staff pages
        [
            'name'    => 'common#index',
            'url'     => '/staff/',
            'verb'    => 'GET',
            'postfix' => 'staff',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/staff/new/',
            'verb'    => 'GET',
            'postfix' => 'staff.new',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/staff/{staffId}/',
            'verb'    => 'GET',
            'postfix' => 'staff.preview',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/staff/{staffId}/edit/',
            'verb'    => 'GET',
            'postfix' => 'staff.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/staff/{staffId}/edit/roles',
            'verb'    => 'GET',
            'postfix' => 'staff.edit.roles',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/staff/{staffId}/edit/directions',
            'verb'    => 'GET',
            'postfix' => 'staff.edit.directions',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/staff/statistics/{staffId}/',
            'verb'    => 'GET',
            'postfix' => 'staff.statistics.user',
        ],

        // Project pages
        [
            'name'    => 'common#index',
            'url'     => '/projects/',
            'verb'    => 'GET',
            'postfix' => 'projects',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/projects/{projectId}/',
            'verb'    => 'GET',
            'postfix' => 'projects.preview',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/projects/{projectId}/edit/',
            'verb'    => 'GET',
            'postfix' => 'projects.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/projects/{projectId}/edit/staff',
            'verb'    => 'GET',
            'postfix' => 'projects.edit.staff',
        ],

        // Team pages
        [
            'name'    => 'common#index',
            'url'     => '/teams/',
            'verb'    => 'GET',
            'postfix' => 'teams',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/teams/{teamId}/',
            'verb'    => 'GET',
            'postfix' => 'teams.team',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/teams/{teamId}/edit/',
            'verb'    => 'GET',
            'postfix' => 'teams.project.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/teams/{teamId}/edit/staff/',
            'verb'    => 'GET',
            'postfix' => 'teams.project.staff',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/teams/{teamId}/edit/projects/',
            'verb'    => 'GET',
            'postfix' => 'teams.project.projects',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/teams/{teamId}/edit/directions/',
            'verb'    => 'GET',
            'postfix' => 'teams.project.directions',
        ],

        // Settings
        [
            'name'    => 'common#index',
            'url'     => '/settings/',
            'verb'    => 'GET',
            'postfix' => 'userSettings',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/staff/',
            'verb'    => 'GET',
            'postfix' => 'staffSettings.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/staff/settings-staff-global-roles/',
            'verb'    => 'GET',
            'postfix' => 'staffSettings.globalRoles.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/staff/settings-staff-global-roles/{slug}/',
            'verb'    => 'GET',
            'postfix' => 'staffSettings.globalRoles.preview',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/staff/positions/',
            'verb'    => 'GET',
            'postfix' => 'staffSettings.positions.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/staff/positions/new/',
            'verb'    => 'GET',
            'postfix' => 'staffSettings.positions.new',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/staff/positions/{slug}/',
            'verb'    => 'GET',
            'postfix' => 'staffSettings.positions.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/staff/contract-types/',
            'verb'    => 'GET',
            'postfix' => 'staffSettings.contractTypes.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/staff/contract-types/new/',
            'verb'    => 'GET',
            'postfix' => 'staffSettings.contractTypes.new',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/staff/contract-types/{slug}/',
            'verb'    => 'GET',
            'postfix' => 'staffSettings.contractTypes.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/staff/dynamic-fields/',
            'verb'    => 'GET',
            'postfix' => 'staffSettings.dynamicFields.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/staff/global-role-permissions/',
            'verb'    => 'GET',
            'postfix' => 'staffSettings.rightsMatrix.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/directions/',
            'verb'    => 'GET',
            'postfix' => 'directionSettings.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/directions/new/',
            'verb'    => 'GET',
            'postfix' => 'directionSettings.new',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/directions/{slug}/',
            'verb'    => 'GET',
            'postfix' => 'directionSettings.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/projects/',
            'verb'    => 'GET',
            'postfix' => 'projectSettings.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/projects/roles/',
            'verb'    => 'GET',
            'postfix' => 'projectSettings.roles.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/projects/roles/new',
            'verb'    => 'GET',
            'postfix' => 'projectSettings.roles.new',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/projects/roles/{slug}',
            'verb'    => 'GET',
            'postfix' => 'projectSettings.roles.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/projects/stages/',
            'verb'    => 'GET',
            'postfix' => 'projectSettings.stages.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/projects/stages/new',
            'verb'    => 'GET',
            'postfix' => 'projectSettings.stages.new',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/projects/stages/{slug}',
            'verb'    => 'GET',
            'postfix' => 'projectSettings.stages.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/projects/dynamic-fields/',
            'verb'    => 'GET',
            'postfix' => 'projectSettings.dynamicFields.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/projects/global-role-permissions/',
            'verb'    => 'GET',
            'postfix' => 'projectSettings.rightsMatrix.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/customers/',
            'verb'    => 'GET',
            'postfix' => 'customerSettings.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/customers/new',
            'verb'    => 'GET',
            'postfix' => 'customerSettings.new',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/customers/{slug}',
            'verb'    => 'GET',
            'postfix' => 'customerSettings.edit',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/teams/',
            'verb'    => 'GET',
            'postfix' => 'teamSettings.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/teams/roles/',
            'verb'    => 'GET',
            'postfix' => 'teamSettings.roles.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/teams/roles/new',
            'verb'    => 'GET',
            'postfix' => 'teamSettings.roles.new',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/teams/roles/{slug}',
            'verb'    => 'GET',
            'postfix' => 'teamSettings.roles.edit',
        ],

        // Dictionaries
        [
            'name'    => 'common#index',
            'url'     => '/settings/dictionaries/',
            'verb'    => 'GET',
            'postfix' => 'dictionaries.main',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/dictionaries/positions/',
            'verb'    => 'GET',
            'postfix' => 'positionsDictionary',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/dictionaries/positions/new',
            'verb'    => 'GET',
            'postfix' => 'positionsDictionary.new',
        ],
        [
            'name'    => 'common#index',
            'url'     => '/settings/dictionaries/positions/{itemId}/',
            'verb'    => 'GET',
            'postfix' => 'positionsDictionary.item',
        ],

        // Dynamic fields

        [
            'name'    => 'common#index',
            'url'     => '/settings/dynamic-fields/',
            'verb'    => 'GET',
            'postfix' => 'dynamicFields',
        ],
        // User dynamic fields
        [
            'name'    => 'common#index',
            'url'     => '/settings/dynamic-fields/1/',
            'verb'    => 'GET',
            'postfix' => 'dynamicFields.user',
        ],
        // Project dynamic fields
        [
            'name'    => 'common#index',
            'url'     => '/settings/dynamic-fields/2/',
            'verb'    => 'GET',
            'postfix' => 'dynamicFields.project',
        ],

        // Error pages
        [
            'name'    => 'common#index',
            'url'     => '/404/',
            'verb'    => 'GET',
            'postfix' => 'errors.404',
        ],

        //////// PERMISSIONS ////////

        [
            'name' => 'permissions#getGlobalRolesPermissions',
            'url'  => '/ajax/getGlobalRolesPermissions',
            'verb' => 'POST',
        ],
        [
            'name' => 'permissions#saveGlobalRolesPermissions',
            'url'  => '/ajax/saveGlobalRolesPermissions',
            'verb' => 'POST',
        ],
        [
            'name' => 'permissions#editGlobalRolesPermissions',
            'url'  => '/ajax/editGlobalRolesPermissions',
            'verb' => 'POST',
        ],

        //////// DYNAMIC FIELDS ////////

        [
            'name' => 'dynamic_fields#saveDynamicFieldsData',
            'url'  => '/ajax/saveDynamicFieldsData',
            'verb' => 'POST',
        ],
        [
            'name' => 'dynamic_fields#saveDynamicField',
            'url'  => '/ajax/saveDynamicField',
            'verb' => 'POST',
        ],
        [
            'name' => 'dynamic_fields#getDinFieldsDataForRecord',
            'url'  => '/ajax/getDinFieldsDataForRecord',
            'verb' => 'POST',
        ],
        [
            'name' => 'dynamic_fields#getDynamicFieldsForSource',
            'url'  => '/ajax/getDynamicFieldsForSource',
            'verb' => 'POST',
        ],
        [
            'name' => 'dynamic_fields#getDinFieldsTypes',
            'url'  => '/ajax/getDinFieldsTypes',
            'verb' => 'POST',
        ],
        [
            'name' => 'dynamic_fields#deleteDynamicField',
            'url'  => '/ajax/deleteDynamicField',
            'verb' => 'POST',
        ],
        [
            'name' => 'dynamic_fields#saveDynamicFieldsDataMultiple',
            'url'  => '/ajax/saveDynamicFieldsDataMultiple',
            'verb' => 'POST',
        ],

        //////// DYNAMIC FIELDS OPTIONS ////////

        [
            'name' => 'dynamic_fields_option#saveDropdownOption',
            'url'  => '/ajax/saveDropdownOption',
            'verb' => 'POST',
        ],
        [
            'name' => 'dynamic_fields_option#deleteDropdownOption',
            'url'  => '/ajax/deleteDropdownOption',
            'verb' => 'POST',
        ],
        [
            'name' => 'dynamic_fields_option#getDropdownOptions',
            'url'  => '/ajax/getDropdownOptions',
            'verb' => 'POST',
        ],
        [
            'name' => 'dynamic_fields_option#reorderDropdownOptions',
            'url'  => '/ajax/reorderDropdownOptions',
            'verb' => 'POST',
        ],

        //////// TABLE ////////

        [
            'name' => 'table#saveTableFilter',
            'url'  => '/ajax/saveTableFilter',
            'verb' => 'POST',
        ],
        [
            'name' => 'table#saveTableColumnView',
            'url'  => '/ajax/saveTableColumnView',
            'verb' => 'POST',
        ],
        [
            'name' => 'table#saveTableSortColumns',
            'url'  => '/ajax/saveTableSortColumns',
            'verb' => 'POST',
        ],
        [
            'name' => 'table#saveTableSortColumnsMultiple',
            'url'  => '/ajax/saveTableSortColumnsMultiple',
            'verb' => 'POST',
        ],
        [
            'name' => 'table#saveTableSortWithinColumns',
            'url'  => '/ajax/saveTableSortWithinColumns',
            'verb' => 'POST',
        ],
        [
            'name' => 'table#saveTableSortWithinColumnsMultiple',
            'url'  => '/ajax/saveTableSortWithinColumnsMultiple',
            'verb' => 'POST',
        ],
        [
            'name' => 'table#deleteTableColumnView',
            'url'  => '/ajax/deleteTableColumnView',
            'verb' => 'POST',
        ],
        [
            'name' => 'table#deleteTableSortColumns',
            'url'  => '/ajax/deleteTableSortColumns',
            'verb' => 'POST',
        ],
        [
            'name' => 'table#deleteTableSortWithinColumns',
            'url'  => '/ajax/deleteTableSortWithinColumns',
            'verb' => 'POST',
        ],
        [
            'name' => 'table#deleteTableFilter',
            'url'  => '/ajax/deleteTableFilter',
            'verb' => 'POST',
        ],
        [
            'name' => 'table#getConditionsForFilter',
            'url'  => '/ajax/getConditionsForFilter',
            'verb' => 'POST',
        ],

        //////// CUSTOM SETTINGS ////////

        [
            'name' => 'custom_settings#getCustomSettingsList',
            'url'  => '/ajax/getCustomSettingsList',
            'verb' => 'POST'
        ],
        [
            'name' => 'custom_settings#getUserCustomSettings',
            'url'  => '/ajax/getUserCustomSettings',
            'verb' => 'POST'
        ],
        [
            'name' => 'custom_settings#saveUserSettings',
            'url'  => '/ajax/saveUserSettings',
            'verb' => 'POST'
        ],
        [
            'name' => 'custom_settings#getAvailableLanguages',
            'url'  => '/ajax/getAvailableLanguages',
            'verb' => 'GET'
        ],
        [
            'name' => 'custom_settings#changeUserLanguage',
            'url'  => '/ajax/changeUserLanguage',
            'verb' => 'POST'
        ],

        //////// FIELD COMMENTS ////////

        [
            'name' => 'field_comments#getFieldComments',
            'url'  => '/ajax/getFieldComments',
            'verb' => 'POST',
        ],
        [
            'name' => 'field_comments#saveFieldComment',
            'url'  => '/ajax/saveFieldComment',
            'verb' => 'POST',
        ],
        [
            'name' => 'field_comments#deleteFieldComment',
            'url'  => '/ajax/deleteFieldComment',
            'verb' => 'POST',
        ],
        [
            'name' => 'field_comments#getFieldCommentsBySource',
            'url'  => '/ajax/getFieldCommentsBySource',
            'verb' => 'POST',
        ],
    ],
];
