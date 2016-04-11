<?php
/*
 * @author Anakeen
 * @package MAILCONNECTOR
*/
/**
 * Retrieve messages from IMAP folder
 */
use \Dcp\AttributeIdentifiers\Mailbox as MyAttributes;
include_once ("FDL/Class.Doc.php");
/**
 * Retrieve messages from IMAP folder
 * @param Action &$action current action
 * @global int $id Http var : folder mailbox identificator
 */
function mb_retrievemessages(Action & $action)
{
    // Get all the params
    $docid = GetHttpVars("id");
    $dbaccess = $action->GetParam("FREEDOM_DB");
    /**
     * @var Dcp\Family\Mailbox $doc
     */
    $doc = new_Doc($dbaccess, $docid);
    if (!$doc->isAlive()) $action->exitError(sprintf(_("cannot see mb unknow reference %s") , $docid));
    
    $err = $doc->mb_connection();
    if ($err != "") {
        $doc->setValue(MyAttributes::mb_connectedimage, "mailbox_red.png");
        $action->AddWarningMsg($err);
    } else {
        $doc->setValue(MyAttributes::mb_connectedimage, "mailbox_green.png");
        $action->AddWarningMsg(_("connection OK"));
        $err = $doc->mb_retrieveMessages($count);
        $doc->mb_close();
        if ($err != "") $action->AddWarningMsg($err);
        else $action->AddWarningMsg(sprintf(_("%d messages transferred") , $count));
    }
    $doc->modify();
    
    redirect($action, GetHttpVars("redirect_app", "FDL") , GetHttpVars("redirect_act", "FDL_CARD&id=$docid") , $action->GetParam("CORE_STANDURL"));
}
?>
