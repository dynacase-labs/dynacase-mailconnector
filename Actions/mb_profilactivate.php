<?php
/*
 * @author Anakeen
 * @package MAILCONNECTOR
*/
/**
 * Activate profil for messages
 */

include_once ("FDL/Class.Doc.php");
/**
 * Retrieve messages from IMAP folder
 * @param Action &$action current action
 * @global int $id Http var : folder mailbox identificator
 */
function mb_profilactivate(Action & $action)
{
    // Get all the params
    $docid = $action->getArgument("id");
    
    $doc = new_Doc($action->dbaccess, $docid);
    if (!$doc->isAlive()) $action->exitError(sprintf(_("cannot see mb unknow reference %s") , $docid));
    
    $pdocid = $doc->getRawValue("fld_pdocid");
    if ($pdocid) {
        $pdoc = new_Doc($action->dbaccess, $pdocid);
        if ($pdoc->isAlive()) {
            if ($pdoc->profid == 0) {
                $pdoc->setControl();
            }
        } else $action->exitError(_("no message profil document has been defined"));
    }
    
    Redirect($action, getHttpVars("redirect_app", "FREEDOM") , getHttpVars("redirect_act", "FREEDOM_GACCESS&id=$pdocid") , $action->getParam("CORE_STANDURL"));
}
