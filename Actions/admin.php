<?php
/**
 * Display mailboxes
 *
 * @author Anakeen 2006
 * @version $Id: admin.php,v 1.3 2007/10/22 12:42:10 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FREEDOM
 * @subpackage
 */



include_once ("FDL/Lib.Dir.php");



/**
 * view spaces to administrates them
 * @param Action &$action current action
 */
function admin( & $action, $onlymy = false)
{
    $dbaccess = $action->GetParam("FREEDOM_DB");

    $fdoc = new_doc($dbaccess, "MAILBOX");
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/AnchorPosition.js");
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/geometry.js");
    $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/resizeimg.js");



    $filter = array ();
    if ($onlymy)
    {
        $filter[] = "owner=".intval($action->user->id);
    }

    $ls = getChildDoc($dbaccess, 0, 0, "ALL", $filter, $action->user->id, "TABLE", "MAILBOX");
    foreach ($ls as $k=>$v)
    {
        $ls[$k]["ICON"] = $fdoc->getIcon($v["icon"]);
    }

    $action->lay->setBlockData("SPACES", $ls);
    $action->lay->set("ficon", $fdoc->geticon());

    $doc = createDoc($dbaccess, "MAILBOX");
    if ($doc === false)
    {
        $action->lay->set("create", false);
    } else
    {
        $action->lay->set("create", true);
    }

}

function mymailbox( & $action)
{
    admin($action, true);
}
?>
