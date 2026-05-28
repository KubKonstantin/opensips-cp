<?php
/*
 * Copyright (C) 2011 OpenSIPS Project
 *
 * This file is part of opensips-cp, a free Web Control Panel Application for 
 * OpenSIPS SIP server.
 *
 * opensips-cp is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * opensips-cp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// Global configuration parameters used by more then one module
global $config;


// The permissions parameter is also used by more the one module,
// when setting the modules permissions for a certain admin.
// This array has 2 values that will remain unchanged: read-only 
// and read-write.
$config->permissions = array("read-only","read-write","admin");
$config->lockout_failed_attempts = 3;
$config->lockout_block_time = 60;
$config->twoFactor = false;
$config->twoFactorDomain = "OpenSIPS CP"; // Can be set to null for automatic fetching

// Password can be saved in plain text mode by setting 
// $config->admin_passwd_mode to 0 or chyphered mode, by setting it to 1
$config->admin_passwd_mode=1;

// Authentication backend: "local" (default) or "casdoor"
$config->auth_mode = "local";

// Casdoor (OIDC) settings used when $config->auth_mode == "casdoor"
$config->casdoor_endpoint = null; // ex: https://door.example.com
$config->casdoor_client_id = null;
$config->casdoor_client_secret = null;
$config->casdoor_organization = null;
$config->casdoor_application = null;
$config->casdoor_redirect_uri = null; // ex: https://cp.example.com/casdoor_callback.php
$config->casdoor_scope = "openid profile email";
$config->casdoor_username_claim = "Name"; // Casdoor commonly returns "Name" as the account login

?>
