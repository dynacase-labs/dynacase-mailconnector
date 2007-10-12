<?php
/**
 *Retrieve messages from IMAP folder
 *
 * @author Anakeen 2007
 * @version $Id: mb_retrievemessages.php,v 1.1 2007/10/12 16:04:56 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */



include_once("FDL/Class.Doc.php");

/**
 * Retrieve messages from IMAP folder
 * @param Action &$action current action
 * @global id Http var : folder mailbox identificator 
 */
function mb_retrievemessages(&$action) {

  // Get all the params      
  $docid=GetHttpVars("id"); 
  $dbaccess = $action->GetParam("FREEDOM_DB");

  $doc = new_Doc($dbaccess, $docid);
  if (! $doc->isAlive()) $action->exitError(sprintf(_("cannot see unknow reference %s"),$docid));
  
  $err=$doc->mb_connection();
  if ($err != "") {
    $this->setValue("mb_connectedimage","mailbox_red.png");
    $action->AddWarningMsg($err);
  } else  {    
    $doc->setValue("mb_connectedimage","mailbox_green.png");
    $action->AddWarningMsg(_("connection OK"));
    $err=$doc->mb_retrieveMessages($count,false);
    if ($err!="")   $action->AddWarningMsg($err);
    else $action->AddWarningMsg(sprintf(_("%d messages transferred"),$count));
  }
  $doc->modify(); 
  redirect($action,GetHttpVars("redirect_app","FDL"),
	   GetHttpVars("redirect_act","FDL_CARD&id=$docid"),
	   $action->GetParam("CORE_STANDURL"));
}
?>