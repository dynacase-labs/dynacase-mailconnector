<?php

include_once("FDL/Lib.Dir.php");
include_once("FDL/Class.Doc.php");
// include_once("FDL/Class.WDocPropo.php");
function verifymail(&$action) {

  header('Content-type: text/xml; charset=utf-8');
  $action->lay->setEncoding("utf-8");
  $dbaccess = $action->GetParam("FREEDOM_DB"); 

  $myid = $action->user->fid;



  $filters=array();
  $filters[]="owner=".intval($action->user->id);


  $ldoc = getChildDoc($dbaccess, 0,
		      "0", "ALL", $filters,
		      $action->user->id, "LIST","MAILBOX");
  $action->lay->set("none", count($ldoc)==0);
  foreach ($ldoc as $k=>$v) {
    $tdoc[$k]=$v->getValues();
    $tdoc[$k]["id"]=$v->id;
    $tdoc[$k]["title"]=$v->getTitle();
    $count=-1;
    $err=$v->mb_connection();
    if ($err=="") {
      $err=$v->mb_retrieveSubject($count,$subjects);
      $v->mb_close();
    }
    $tsubj=array();
    if ($count>0) foreach ($subjects as $v) $tsubj[]=array("subject"=>$v);
    $tdoc[$k]["subjects"]="SUBJECT$k";
    $action->lay->setBlockData("SUBJECT$k", $tsubj);
    $tdoc[$k]["mesg"]="";
    $tdoc[$k]["error"]="";
    $tdoc[$k]["nothing"]="";
    $tdoc[$k]["count"]=($count>0);
    if ($err) $tdoc[$k]["error"]=$err;
    else  {
      
      if ($count==0) $tdoc[$k]["nothing"]=sprintf(_("no new messages"));
      else if ($count==1) $tdoc[$k]["mesg"]=sprintf(_("one new message"));
      else if ($count>1) $tdoc[$k]["mesg"]=sprintf(_("%s new messages"),$count);
      
    }    
  }

  $action->lay->setBlockData("inc", $tdoc);
  $action->lay->set("location", sprintf(_("exchange mailboxes  -%d-"),count($tdoc)));

  $action->lay->set("uptime", strftime("%H:%M %d/%m/%Y", time()));
  
}
?>