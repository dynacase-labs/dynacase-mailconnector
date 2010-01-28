<?php
/**
 * Activate profil for messages
 *
 * @author Anakeen 2009
 * @version $Id:  $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package MAILCONNECTOR
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
function mb_profilactivate(&$action) {

  // Get all the params      
  $docid=GetHttpVars("id"); 
  $dbaccess = $action->GetParam("FREEDOM_DB");

  $doc = new_Doc($dbaccess, $docid);
  if (! $doc->isAlive()) $action->exitError(sprintf(_("cannot see unknow reference %s"),$docid));
  
  $pdocid=$doc->getValue("fld_pdocid");
  if ($pdocid) {
    $pdoc=new_doc($dbaccess,$pdocid);
    if ($pdoc->isAlive()) {
      if ($pdoc->profid==0) {
	$pdoc->setControl();	
      }
    } else $action->exitError(_("no message profil document has been defined"));
  }
   

  redirect($action,GetHttpVars("redirect_app","FREEDOM"),
	   GetHttpVars("redirect_act","FREEDOM_GACCESS&id=$pdocid"),
	   $action->GetParam("CORE_STANDURL"));
}
?>