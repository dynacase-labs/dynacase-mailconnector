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
    $docid = getHttpVars("id");
    /**
     * @var \Dcp\Family\Mailbox $doc
     */
    $doc = new_Doc($action->dbaccess, $docid);
    if (!$doc->isAlive()) $action->exitError(sprintf(_("cannot see mb unknow reference %s") , $docid));
    
    $err = $doc->mb_connection();
    if ($err != "") {
        $doc->setValue(MyAttributes::mb_connectedimage, "mailbox_red.png");
        $action->addWarningMsg($err);
    } else {
        $doc->setValue(MyAttributes::mb_connectedimage, "mailbox_green.png");
        $action->addWarningMsg(_("connection OK"));
        $doc->mb_retrieveSubject($count, $nothing); // just count
        if ($err != "") $action->addWarningMsg($err);
        else $action->addWarningMsg(sprintf(_("%d messages to transferts") , $count));
    }
    $doc->modify();
    Redirect($action, getHttpVars("redirect_app", "FDL") , getHttpVars("redirect_act", "FDL_CARD&id=$docid") , $action->getParam("CORE_STANDURL"));
}
