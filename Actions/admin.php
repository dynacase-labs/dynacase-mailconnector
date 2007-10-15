<?php
/**
 * Display mailboxes
 *
 * @author Anakeen 2006
 * @version $Id: admin.php,v 1.1 2007/10/15 16:28:23 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */



include_once("FDL/Lib.Dir.php");



/**
 * view spaces to administrates them
 * @param Action &$action current action
 */
function admin(&$action,$onlymy=false) {  
  $dbaccess = $action->GetParam("FREEDOM_DB");

  $fdoc=new_doc($dbaccess,"MAILBOX");
  


  $filter=array();
  if ($onlymy) $filter[]="owner=".intval($action->user->id);

  $ls = getChildDoc($dbaccess, 0 ,0,"ALL", $filter, $action->user->id, "TABLE","MAILBOX");
  foreach ($ls as $k=>$v) {
    $ls[$k]["ICON"]=$fdoc->getIcon($v["icon"]);
  }

  $action->lay->setBlockData("SPACES",$ls);
  $action->lay->set("ficon",$fdoc->geticon());

}

function mymailbox(&$action) {
  admin($action,true);
}
?>