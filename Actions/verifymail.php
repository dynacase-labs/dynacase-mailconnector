<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package MAILCONNECTOR
*/

include_once ("FDL/Lib.Dir.php");
include_once ("FDL/Class.Doc.php");

function verifymail(Action & $action)
{
    
    header('Content-type: text/xml; charset=utf-8');
    
    $s = new SearchDoc($action->dbaccess, Dcp\Family\Mailbox::familyName);
    $s->addFilter("owner=%d", $action->user->id);
    $s->setObjectReturn(true);
    $s->search();
    $ldoc = $s->getDocumentList();
    $action->lay->set("none", count($ldoc) == 0);
    $tdoc = array();
    foreach ($ldoc as $k => $v) {
        /**
         * @var  Dcp\Family\Mailbox $v
         */
        $tdoc[$k] = $v->getValues();
        $tdoc[$k]["id"] = $v->id;
        $tdoc[$k]["title"] = $v->getTitle();
        $count = - 1;
        $err = $v->mb_connection();
        
        $subjects = array();
        if ($err == "") {
            $v->mb_retrieveSubject($count, $subjects);
            $v->mb_close();
        }
        $tsubj = array();
        if ($count > 0) foreach ($subjects as $vs) $tsubj[] = array(
            "subject" => $vs
        );
        $tdoc[$k]["subjects"] = "SUBJECT$k";
        $action->lay->setBlockData("SUBJECT$k", $tsubj);
        $tdoc[$k]["mesg"] = "";
        $tdoc[$k]["error"] = "";
        $tdoc[$k]["nothing"] = "";
        $tdoc[$k]["count"] = ($count > 0);
        if ($err) $tdoc[$k]["error"] = $err;
        else {
            
            if ($count == 0) $tdoc[$k]["nothing"] = sprintf(_("no new messages"));
            else if ($count == 1) $tdoc[$k]["mesg"] = sprintf(_("one new message"));
            else if ($count > 1) $tdoc[$k]["mesg"] = sprintf(_("%s new messages") , $count);
        }
    }
    
    $action->lay->setBlockData("inc", $tdoc);
    $action->lay->set("location", sprintf(_("exchange mailboxes  -%d-") , count($tdoc)));
    
    $action->lay->set("uptime", strftime("%H:%M %d/%m/%Y", time()));
}
?>
