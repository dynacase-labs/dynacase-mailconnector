<?php
/**
 * Test IMAP connection
 *
 * @author Anakeen 2007
 * @version $Id: mb_testconnection.php,v 1.4 2007/10/23 15:30:39 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */



include_once("FDL/Class.Doc.php");

/**
 * Test IMAP connection
 * @param Action &$action current action
 * @global id Http var : folder mailbox identificator to test
 */
function mb_testconnection(&$action) {

  // Get all the params      
  $docid=GetHttpVars("id"); 
  $dbaccess = $action->GetParam("FREEDOM_DB");

  $doc = new_Doc($dbaccess, $docid);
  if (! $doc->isAlive()) $action->exitError(sprintf(_("cannot see unknow reference %s"),$docid));
  
  $err=$doc->mb_connection();
  if ($err != "") {
    $doc->setValue("mb_connectedimage","mailbox_red.png");
    $action->AddWarningMsg($err);
  } else  {    
    $doc->setValue("mb_connectedimage","mailbox_green.png");
    $action->AddWarningMsg(_("connection OK"));
    $err=$doc->mb_retrieveSubject($count,$nothing); // just count
    if ($err!="")   $action->AddWarningMsg($err);
    else $action->AddWarningMsg(sprintf(_("%d messages to transferts"),$count));
  }
  $doc->modify(); 
  redirect($action,GetHttpVars("redirect_app","FDL"),
	   GetHttpVars("redirect_act","FDL_CARD&id=$docid"),
	   $action->GetParam("CORE_STANDURL"));
}
?>