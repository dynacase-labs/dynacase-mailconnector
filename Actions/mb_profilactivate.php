<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
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
    $dbaccess = $action->GetParam("FREEDOM_DB");
    
    $doc = new_Doc($dbaccess, $docid);
    if (!$doc->isAlive()) $action->exitError(sprintf(_("cannot see mb unknow reference %s") , $docid));
    
    $pdocid = $doc->getRawValue("fld_pdocid");
    if ($pdocid) {
        $pdoc = new_doc($dbaccess, $pdocid);
        if ($pdoc->isAlive()) {
            if ($pdoc->profid == 0) {
                $pdoc->setControl();
            }
        } else $action->exitError(_("no message profil document has been defined"));
    }
    
    redirect($action, GetHttpVars("redirect_app", "FREEDOM") , GetHttpVars("redirect_act", "FREEDOM_GACCESS&id=$pdocid") , $action->GetParam("CORE_STANDURL"));
}
?>
