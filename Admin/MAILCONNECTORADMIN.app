<?php
// ---------------------------------------------------------------
// $Id: MAILCONNECTOR.app,v 1.4 2007/10/17 15:44:17 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/mailconnector/MAILCONNECTOR.app,v $
// ---------------------------------------------------------------
//  O   Anakeen - 2001
// O*O  Anakeen development team
//  O   dev@anakeen.com
// ---------------------------------------------------------------
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or (at
//  your option) any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
// or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
// for more details.
//
// You should have received a copy of the GNU General Public License along
// with this program; if not, write to the Free Software Foundation, Inc.,
// 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
// ---------------------------------------------------------------

$app_desc = array(
    "name" => "MAILCONNECTORADMIN", //Name
    "short_name" => N_("Mail connector admin"), //Short name
    "description" => N_("Manage linked mailboxes"), //long description
    "access_free" => "N", //Access free ? (Y,N)
    "icon" => "mailboxadmin.png", //Icon
    "displayable" => "N", //Should be displayed on an app list (Y,N)
    "with_frame" => "Y", //Use multiframe ? (Y,N)
    "tag" => "admin" // i
);

$app_acl = array(
    array(
        "name" => "ADMIN",
        "description" => N_("Access admin mailboxes"),
        "group_default" => "N"),
);

$action_desc = array(

    array(
        "name" => "ADMIN",
        "root"=>"Y",
        "short_name" => N_("View and create new mailboxes"),
        "acl" => "ADMIN")
);



?>
