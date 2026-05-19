<?php

return [
    'title'             => 'Users',
    'subtitle'          => 'System user account management',
    'new_user'          => 'New User',
    'edit_user'         => 'Edit User',
    'add_subtitle'      => 'Add a user account to the system',
    'back'              => '← Back',
    'save_user'         => 'Save User',
    'save_changes'      => 'Save Changes',
    'cancel'            => 'Cancel',
    'edit_btn'          => 'Edit',
    'no_users'          => 'No users recorded.',

    // Table columns
    'col_user'          => 'User',
    'col_email'         => 'Email',
    'col_unit'          => 'Unit',
    'col_role'          => 'Role',
    'col_status'        => 'Status',
    'col_supervisor'    => 'Supervisor',
    'col_actions'       => 'Actions',
    'active'            => 'Active',
    'inactive'          => 'Inactive',

    // Form sections
    'form_personal'     => 'Personal Information',
    'form_org'          => 'Organisational Structure',
    'form_security'     => 'Security',
    'errors_title'      => 'Please fix the following errors:',

    // Form fields
    'field_name'        => 'Full Name',
    'field_name_ph'     => 'First and last name',
    'field_email'       => 'Email',
    'field_phone'       => 'Phone',
    'field_job_title'   => 'Job Title',
    'field_unit'        => 'Unit',
    'field_unit_ph'     => '— Select unit —',
    'field_role'        => 'Role',
    'field_supervisor'  => 'Direct Supervisor',
    'field_supervisor_ph'=> '— No supervisor —',
    'field_is_active'   => 'Active account',
    'field_password'    => 'Password',
    'field_password_hint'=> '(Leave blank to keep unchanged)',
    'field_password_ph' => 'New password',
    'invite_notice'     => 'An invitation email will be sent to this address with a link to set their password. No password is required now.',
    'invited_success'           => ':name has been added and an invitation email has been sent.',
    'deactivate_pending_warning'=> 'Cannot deactivate: this user has :count pending approval(s). Reassign or resolve those requests first.',

    // Index page
    'search_placeholder' => 'Search by name, email, or staff number…',
    'stat_total'         => 'Total',
    'stat_total_sub'     => 'Registered accounts',
    'stat_active_sub'    => 'Active accounts',
    'stat_inactive_sub'  => 'Disabled accounts',

    // Create sidebar
    'create_info_title'  => 'New User Account',
    'create_info_body'   => 'Fill in the staff details, assign the correct role and unit. A temporary password will be generated and the user will receive an invitation email.',
    'role_guide'         => 'Role Guide',

    // Edit sidebar
    'edit_note_title'    => 'Note',
    'edit_note_body'     => 'Changing a user\'s role or unit takes effect on their next submitted travel request.',

    // Role guide descriptions
    'role_desc_staff'            => 'Submits travel requests. No approval responsibilities.',
    'role_desc_head'             => 'Section head; reviews requests before the director.',
    'role_desc_manager'          => 'Senior staff level with limited approval scope.',
    'role_desc_director'         => 'HQ directorate approver, acts before DG.',
    'role_desc_centre_manager'   => 'Final approver for research centre staff.',
    'role_desc_director_general' => 'Ultimate final approver for all HQ requests.',
    'role_desc_hr'               => 'Receives notification copies only — cannot approve.',
    'role_desc_system_admin'     => 'Manages user accounts, roles, and unit assignments.',
];
