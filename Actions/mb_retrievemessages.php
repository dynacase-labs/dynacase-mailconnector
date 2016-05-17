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
    $docid = getHttpVars("id");
    /**
     * @var Dcp\Family\Mailbox $doc
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
        $err = $doc->mb_retrieveMessages($count);
        $doc->mb_close();
        if ($err != "") $action->addWarningMsg($err);
        else $action->addWarningMsg(sprintf(_("%d messages transferred") , $count));
    }
    $doc->modify();
    
    Redirect($action, getHttpVars("redirect_app", "FDL") , getHttpVars("redirect_act", "FDL_CARD&id=$docid") , $action->getParam("CORE_STANDURL"));
}
