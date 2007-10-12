<?php

var $defaultview="MAILCONNECTOR:VIEWEMESSAGE";

function viewemessage($target="_self",$ulink=true,$abstract=false) {
  include_once("FDL/Lib.Dir.php");
  $this->viewdefaultcard($target,$ulink,$abstract);


  $from=$this->getValue("emsg_from");
  if (ereg("<([^>]*)>",$from,$erg)) {
    $from=$erg[1];
  }
  $this->lay->set("hasphoto",false);
  $filter1="us_mail='".pg_escape_string($from)."'";
  $filter2="us_homemail='".pg_escape_string($from)."'";
  $filter[]="$filter1 or $filter2";
  $tdir=getChildDoc($this->dbaccess,0,"0",1,$filter,1,"LIST","USER");
  if (count($tdir)==1) {

    $vphoto=$tdir[0]->getValue("us_photo");
    if ($vphoto) {
      $photo=$tdir[0]->GetHtmlAttrValue("us_photo");
      $this->lay->set("photo",$photo);
      $this->lay->set("hasphoto",($photo!=""));
    }
  }
  $hashtml=($this->getValue("emsg_htmlbody")!="");

  $this->lay->set("hashtml",$hashtml);
  
}

?>