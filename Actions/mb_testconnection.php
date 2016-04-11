<?php
/*
 * @author Anakeen
 * @package MAILCONNECTOR
*/
/**
 * Test IMAP connection
 */
use \Dcp\AttributeIdentifiers\Mailbox as MyAttributes;
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
     * @var \Dcp\Family\Mailbox $doc
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
        $doc->mb_retrieveSubject($count, $nothing); // just count
        if ($err != "") $action->AddWarningMsg($err);
        else $action->AddWarningMsg(sprintf(_("%d messages to transferts") , $count));
    }
    $doc->modify();
    redirect($action, GetHttpVars("redirect_app", "FDL") , GetHttpVars("redirect_act", "FDL_CARD&id=$docid") , $action->GetParam("CORE_STANDURL"));
}
?>
