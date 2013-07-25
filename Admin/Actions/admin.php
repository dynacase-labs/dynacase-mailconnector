<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package MAILCONNECTOR
*/
/**
 * Display mailboxes
 */

include_once ("FDL/Lib.Dir.php");
/**
 * view spaces to administrates them
 * @param Action &$action current action
 */
function admin(&$action, $onlymy = false)
{
    $dbaccess = $action->dbaccess;
    
    $fdoc = new_doc($dbaccess, Dcp\Family\Mailbox::familyName);
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL") . "/AnchorPosition.js");
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL") . "/geometry.js");
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL") . "/resizeimg.js");
    
    $s = new SearchDoc($dbaccess, Dcp\Family\Mailbox::familyName);
    if ($onlymy) {
        $s->addFilter("owner=%d", $action->user->id);
    }
    
    $ls = $s->search();
    foreach ($ls as $k => $v) {
        $ls[$k]["ICON"] = $fdoc->getIcon($v["icon"]);
    }
    
    $action->lay->setBlockData("SPACES", $ls);
    $action->lay->set("ficon", $fdoc->geticon());
    
    $doc = createDoc($dbaccess, Dcp\Family\Mailbox::familyName);
    if ($doc === false) {
        $action->lay->set("create", false);
    } else {
        $action->lay->set("create", true);
    }
}

function mymailbox(&$action)
{
    admin($action, true);
}
?>
