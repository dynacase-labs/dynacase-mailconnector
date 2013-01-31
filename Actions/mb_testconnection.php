<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package MAILCONNECTOR
*/
/**
 * Test IMAP connection
 */

include_once ("FDL/Class.Doc.php");
/**
 * Test IMAP connection
 * @param Action &$action current action
 * @global int $id Http var : folder mailbox identificator to test
 */
function mb_testconnection(&$action)
{
    // Get all the params
    $docid = GetHttpVars("id");
    $dbaccess = $action->GetParam("FREEDOM_DB");
    /**
     * @var _MAILBOX $doc
     */
    $doc = new_Doc($dbaccess, $docid);
    if (!$doc->isAlive()) $action->exitError(sprintf(_("cannot see unknow reference %s") , $docid));
    
    $err = $doc->mb_connection();
    if ($err != "") {
        $doc->setValue("mb_connectedimage", "mailbox_red.png");
        $action->AddWarningMsg($err);
    } else {
        $doc->setValue("mb_connectedimage", "mailbox_green.png");
        $action->AddWarningMsg(_("connection OK"));
        $doc->mb_retrieveSubject($count, $nothing); // just count
        if ($err != "") $action->AddWarningMsg($err);
        else $action->AddWarningMsg(sprintf(_("%d messages to transferts") , $count));
    }
    $doc->modify();
    redirect($action, GetHttpVars("redirect_app", "FDL") , GetHttpVars("redirect_act", "FDL_CARD&id=$docid") , $action->GetParam("CORE_STANDURL"));
}
?>
