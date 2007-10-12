<?php

function getimapfolders($mboxid) {
  $dbaccess=getParam("FREEDOM_DB");
  $mb=new_doc($dbaccess,$mboxid);
  if ($mb->isAlive()) {
    $err=$mb->mb_connection();
    if ($err=="") {
      $list=imap_list($mb->mbox, $mb->fimap, "*");
      foreach ($list as $k=>$fld) {
	$f=substr($fld,strpos($fld,'}')+1);
	$tr[]=array($f,$f);
      }
      print_r2($list);
    }
  }


  $tr[] = array("toto" ,$mb->title);
  $tr[] = array("tot2-$mboxid" ,$mb->title);
  if ($err) return $err;
  return $tr;
  }

?>