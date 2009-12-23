<?php

public $mbox;

function postcreated() {
  $err=$this->mb_setProfil();
  if ($err=="") {
    $home=$this->getHome();
    if ($home) $home->AddFile($this->id);
  }

  return $err;
}

function specRefresh() {
  if ($this->id == 0) {
    $oa=$this->getAttribute("mb_folder");
    $oa->mvisibility='S'; // need test connection before
  }
  
}

function minDelay($t) {  
  $sug=array();

  if (($t > 0) && ($t < 5)) $err=sprintf(_("min delay is 5 minutes"));
  if ($err) $sug=array("0",5,10,15,30,60,120);
  return array("err"=>$err,
		"sug"=>$sug);
}

/**
 * set personnal profil by default
 */
function mb_setProfil() {
  if ($this->getValue("fld_pdocid")=="") {

    $pp=createDoc($this->dbaccess,"PDIR",false);
    $pp->setValue("ba_title",sprintf(_("profil for %s mailbox"),$this->title));
    $pp->setValue("prf_desc",sprintf(_("associated default profil for [ADOC %s] mailbox"),$this->id));
    $err=$pp->add();
    if ($err=="") {
      $this->setValue("fld_pdocid",$pp->id);
      $this->setValue("fld_pdirid",$pp->id);

      $mpp=getMyProfil($this->dbaccess);
      if ($mpp->isAlive()) {
	$this->setProfil($mpp->id);
      }
      $err=$this->modify();
    }
  }
  return $err;
  
}

function mb_connection() {
  include_once("FDL/Lib.Vault.php");
  $login=$this->getValue("mb_login");
  $password=$this->getValue("mb_password");
  $server=$this->getValue("mb_servername");
  $port=$this->getValue("mb_serverport");
  $ssl=$this->getValue("mb_security");


  if ($ssl=="SSL") $this->fimap=sprintf("{%s:%d/imap/ssl/novalidate-cert}",$server,$port);
  else $this->fimap=sprintf("{%s:%d/imap/notls}",$server,$port);  

  $this->imapconnection=$this->fimap."INBOX";
  
  imap_timeout(1,getParam("MB_TIMEOUT",5)); // 5 seconds
  $this->mbox = @imap_open($this->imapconnection,$login ,$password );
  if (!$this->mbox) {
    $err=imap_last_error();
  } 
  return $err;
}

/**
 * retrieve unflagged messages from specific folder
 * @param int  &$count return number of messages transffered
 */
function mb_retrieveMessages(&$count,$fdir="") { 
  if ($this->getValue("mb_recursive")=="yes") $err=$this->mb_recursiveRetrieveMessages($count);
  else $err=$this->mb_retrieveFolderMessages($count);
}
/**
 * retrieve unflagged messages from specific folder (not recursive)
 * @param int  &$count return number of messages transffered
 * @param string $fdir imap sub folder
 */
function mb_retrieveFolderMessages(&$count,$fdir="") {   
  if ($fdir=="") {
   $folder=$this->getValue("mb_folder","INBOX");
   $fdir=$this->fimap.mb_convert_encoding($folder, "UTF7-IMAP","UTF8");
  }

  $err=$this->control("modify");
  if ($err=="") {
    if (!@imap_reopen($this->mbox,$fdir)) {
      $err=sprintf(_("imap folder %s not found : %s"), $folder,imap_last_error());    
    } 
  }

  if ($err=="") {     
    $pa=$this->getValue("mb_postaction","tag");
    $movetofolder=$this->getValue("mb_movetofolder");
    $msgs = imap_search($this->mbox,'UNFLAGGED UNDELETED' );

    if (is_array($msgs)) {              
	$count=0;
	foreach ($msgs as $k=>$val) {
	  $err=$this->mb_parseMessage($val);	
	  if ($err=="") {
	    $status = imap_setflag_full($this->mbox, $val, '\\Flagged');
	    switch ($pa) {
	    case "tag":
	      //$status = imap_clearflag_full($this->mbox, $msg, "\\Seen");
	      //$status = imap_setflag_full($this->mbox, $msg, '$label3');
	      break;
	    case "delete":
	      $status = imap_delete($this->mbox, $val);
	      break;
	    case "move":
	      if ($movetofolder) {
		imap_mail_move($this->mbox, "$val:$val",$movetofolder);	      
	      }
	      break;
	    }
	    $count++;
	  }
	  else addWarningMsg($err);
	}
	if ($this->getValue("mb_purge")=="yes") {	  
	      imap_expunge($this->mbox);
	}

	$this->AddComment(sprintf(_("%d messages transfered"),$count));
          
    } else {
      $count=0;
    }     
  }  
  
  return $err;

}

function mb_close() {
  @imap_close($this->mbox);
}


/**
 * retrieve subject of unflagged messages from specific folder
 * @param int  &$count return number of messages transffered
 * @param bool $justcount set to true to just return number of messages
 */
function mb_retrieveSubject(&$count,&$subject,$limit=5) {   
  if ($this->getValue("mb_recursive")=="yes") {
    $folder=$this->getValue("mb_folder","INBOX");
    $fdir=$this->fimap.mb_convert_encoding($folder, "UTF7-IMAP","UTF8");
    $folders = imap_list($this->mbox, $fdir, "*");
    $count=0;
    $subject=array();
    if ($folders!=FALSE) { //if there is an error imap_list returns FALSE not an empty array (as expected by docs)
                           //or if $folders is an empty array
      /*print "count de folders: ".count($folders);
      print "contenu de folders:";
      print_r2($folders);
      print "fin de folders";*/
      foreach ($folders as $k=>$subfld) {
        $subsubject=array();
        $this->mb_retrieveFolderSubject($subcount,$subsubject,$limit-$count,$subfld);
        $count+=$subcount;
        $subject=array_merge($subject,$subsubject);
      }
    }
  } else {
    $this->mb_retrieveFolderSubject($count,$subject,$limit);  
  }
} 
/**
 * retrieve subject of unflagged messages from specific folder
 * @param int  &$count return number of messages transffered
 * @param bool $justcount set ti true to just return number of messages
 */
function mb_retrieveFolderSubject(&$count,&$subject,$limit=5,$fdir="") {  
  if ($fdir=="") {
   $folder=$this->getValue("mb_folder","INBOX");
   $fdir=$this->fimap.mb_convert_encoding($folder, "UTF7-IMAP","UTF8");
  } 
  
  $subject=array();
  if (!@imap_reopen($this->mbox,$fdir)) {
    $err=sprintf(_("imap folder %s not found"), $folder);    
  }

  if ($err=="") {     
    $msgs = imap_search($this->mbox,'UNFLAGGED UNDELETED' );
    if (is_array($msgs)) {
      $count=count($msgs);       
      $c=0;
      $seq=array();
      while (($c<$limit) && ($c<$count)) {
	$seq[]=$msgs[$c];
	$c++;
      }
      $sseq=implode(",",$seq);    
      $over=imap_fetch_overview($this->mbox,$sseq);
      
      foreach ($over as $k=>$v) {
	$subject[]=$this->mb_decode($v->subject);
      }
    } else {
      $count=0;
    }
     
  }  
  return $err;

}
function postModify() {
  $port=$this->getValue("mb_serverport");
  $security=$this->getValue("mb_security");
  if (($port=="") && ($security!="SSL")) $this->setValue("mb_serverport",143);
  else if (($port=="") && ($security=="SSL")) $this->setValue("mb_serverport",993);
  else if (($port=="143") && ($security=="SSL")) $this->setValue("mb_serverport",993);
  else if (($port=="993") && ($security!="SSL")) $this->setValue("mb_serverport",143);
  if (intval($this->getValue("mb_autoretrieve")) > 0) $err=$this->createMbProcessus();
  else $err=$this->deleteMbProcessus();
  return $err;
}

function postDelete() {
  $err=$this->deleteMbProcessus();
  return $err;
}
function deleteMbProcessus() {
  $name="EXECMB_".$this->initid;
  $doc=new_doc($this->dbaccess,$name);
  $err="";
  if ($doc->isAlive()) {
    $err=$doc->delete(true,false);
  }
  return $err;
}

function createMbProcessus() {
  $name="EXECMB_".$this->initid;
  $doc=new_doc($this->dbaccess,$name);
  $err="";
  if (! $doc->isAlive()) {
    $doc=createDoc($this->dbaccess,"EXEC",false);
    if ($doc) {
      $doc->disableEditControl();
      $doc->name=$name;
      $doc->setValue("exec_application","MAILCONNECTOR");
      $doc->setValue("exec_action","MB_RETRIEVEMESSAGES");

      $doc->addArrayRow("exec_t_parameters",array("exec_idvar"=>"id",
						  "exec_valuevar"=>$this->id));


      $doc->setValue("exec_periodmin",$this->getValue("mb_autoretrieve"," "));
      $doc->setValue("exec_handnextdate",$this->getTimeDate());
      $doc->setValue("exec_title",sprintf(_("Retrieve messages for %s mailbox"),$this->getTitle()));
      $err=$doc->add();
      if ($err=="") {
	$doc->postModify();
	$doc->refresh();
      }
    }
  }
  return $err;
}
/**
 * decode headers text
 * @param string $s encoded text
 * @return string utf-8 text
 */
static function mb_decode($s) {
 $t=imap_mime_header_decode($s);
 $ot=''; 
 foreach ($t as $st) {
   if ($st->charset && $st->charset!="default")  $ot.=@mb_convert_encoding($st->text, "UTF-8",$st->charset );
   else $ot.=$st->text;
 }

 return $ot;
}


function mb_parseMessage($msg) {
  //print "<hr>";
  
  $h= imap_header($this->mbox,$msg);
  $uid=$h->message_id;
  //    print "<b>".$this->mb_decode($h->subject)."</b> [$uid]";
  
  if ($uid=="") $uid=$h->date.'-'.$h->Size;  
  //	print("<b>$body</b>");
  $this->msgStruct=array();
   $this->msgStruct["subject"]=$this->mb_decode($h->subject);
   $this->msgStruct["uid"]=$uid;


   $this->msgStruct["date"]=strftime("%Y-%m-%d %H:%M:%S",strtotime($h->date));;
   $this->msgStruct["to"]=$this->mb_implodemail($h->to);
   $this->msgStruct["from"]=$this->mb_implodemail($h->from);
   $this->msgStruct["cc"]=$this->mb_implodemail($h->cc);
   $this->msgStruct["size"]=$h->Size;



   $o=imap_fetchstructure($this->mbox,$msg);
   //   print_r2($o);
   if ($o->subtype=="PLAIN") {
     $body=imap_body($this->mbox,$msg);
     $this->mb_bodydecode($o,$body);
     $this->msgStruct["textbody"]=$body;
   } else  if ($o->subtype=="HTML") {
     $body=imap_body($this->mbox,$msg);
     $this->mb_bodydecode($o,$body);
     $this->msgStruct["htmlbody"]=$body;
     
   
   } else  if ($o->subtype=="ALTERNATIVE") {
     if ($o->parts[0]->subtype=="PLAIN") {
       $body=imap_fetchbody($this->mbox,$msg,'1'); 
       $this->mb_bodydecode($o->parts[0],$body);              
       $this->msgStruct["textbody"]=$body;
     }
     if ($o->parts[1]->subtype=="HTML") {
       $body=imap_fetchbody($this->mbox,$msg,'2');
       $this->mb_bodydecode($o->parts[1],$body);
       $this->msgStruct["htmlbody"]=$body;       
     } else if ($o->parts[1]->subtype=="RELATED") {
       $this->mb_getmultipart($o->parts[1],$msg,'2.');
     } else if ($o->parts[1]->subtype=="MIXED") {
       $this->mb_getmultipart($o->parts[1],$msg,'2.');
     } else if ($o->parts[1]->subtype=="ALTERNATIVE") {
       $this->mb_getmultipart($o->parts[1],$msg,'2.');
     }
   } else if ($o->subtype=="MIXED") {
     $this->mb_getmultipart($o,$msg);
    
   } else if ($o->subtype=="RELATED") {
     $this->mb_getmultipart($o,$msg);
   }


   $this->mb_getcid($msg);
   $err=$this->mb_createMessage();
   

   return $err;

}

/**
 * decode part of mail
 * @param stdClass $part part object from imap_fetchstructure
 * @param string &$body the message to decode
 * 
 */
function mb_bodydecode($part,&$body) {
  switch ($part->encoding) {
  case 3: // base64
    $body=imap_base64($body);
    break;
  case 0: // 7bit
    break;
  case 1: // 8bit
    break;
  case 2: //Binary
    break;
  case 4: // QUOTED-PRINTABLE
    $body=quoted_printable_decode($body);
    break;
  case 5: // Others
    break;
  }

  if ($part->ifparameters) {
    foreach ($part->parameters as $v) {
      if (strtolower($v->attribute)=="charset") {
	if ($v->value && $v->value!="default")  $body=@mb_convert_encoding($body,"UTF-8",$v->value);
      }
    }
  }
}

/**
 * analyse multipart of mail
 * @param stdClass $o part object from imap_fetchstructure
 * @param int $msg the message identificator from imap_search
 * @param string $chap index of chapter like 2.3
 * 
 */
function mb_getmultipart($o,$msg,$chap="") {
   foreach ($o->parts as $k=>$part) {

     //     print "<ul><b>".sprintf("$chap%d",$k+1)."</b></ul>";
       if ($part->subtype=="PLAIN") {
	 $body=imap_fetchbody($this->mbox,$msg,sprintf("$chap%d",$k+1));
	 
	 $this->mb_bodydecode($part,$body);


	 $this->msgStruct["textbody"]=$body;
       } else  if ($part->subtype=="HTML") {
	 $body=imap_fetchbody($this->mbox,$msg,sprintf("$chap%d",$k+1));
	 	 
	 $this->mb_bodydecode($part,$body);
	 $this->msgStruct["htmlbody"]=$body;       
       } else {
	 $part->disposition=strtoupper($part->disposition);
	 if (($part->disposition=="INLINE")||($part->disposition=="ATTACHMENT")) {
	   $body=imap_fetchbody($this->mbox,$msg,sprintf("$chap%d",$k+1));
	   $this->mb_bodydecode($part,$body);
	   $basename="";
	   if ($part->ifdparameters) {
	     foreach ($part->dparameters as $param) {
	       $param->attribute=strtok(strtoupper($param->attribute),'*');
	       if ($param->attribute=="FILENAME") $basename=basename($this->mb_decode(urldecode($param->value)));	      
	     }
	   }
	   if ($part->ifparameters) {
	     foreach ($part->parameters as $param) {
	       $param->attribute=strtoupper($param->attribute);
	       if ($param->attribute=="NAME") $basename=$this->mb_decode($param->value);
	     }
	   }
	   $filename=uniqid("/var/tmp/_fdl").'.'.strtolower($part->subtype);
	   $nc=file_put_contents($filename,$body);
	   $this->msgStruct["file"][]=$filename;
	   $this->msgStruct["basename"][]=$basename;
	   // $this->msgStruct["cid"][]=$cid;
	 } else  if (($part->subtype=="RELATED")||($part->subtype=="ALTERNATIVE")) {
	   $this->mb_getmultipart($part,$msg,sprintf("$chap%d.",$k+1));
	 }
       }
     }
}

/**
 * recompose mail address from structure
 * @param stdClass $struct objets from imap_header
 * @return string mail address like John Doe <jd@somewhere.ord>
 */
static function mb_implodemail($struct) {
  $tmail=array();
  if (! is_array($struct)) return false;
  foreach ($struct as $k=>$v) {
    $email=$v->mailbox.'@'.$v->host;
    if (isset($v->personal)) $email=self::mb_decode($v->personal).' <'.$email.'>';  

    
    $tmail[$k]=$email;
  }

  return implode(";",$tmail);
}

/**
 * create electronic message document from $this->msgStruct
 */
function mb_createMessage() {
  include_once("FDL/Lib.Dir.php");

  $uid=pg_escape_string($this->msgStruct["uid"]);
  $filter[]="emsg_uid='$uid'";
  $filter[]="emsg_mailboxid='".$this->initid."'";
  $fammsg=$this->getValue("mb_msg_family","EMESSAGE");

  $tdir=getChildDoc($this->dbaccess,0,"0",1,$filter,1,"LIST",$fammsg);
  if (count($tdir)==0) {
    $msg=createdoc($this->dbaccess,$fammsg);    
  } else {
    $msg=$tdir[0];
  }
  if ($msg) {
      $msg->setValue("emsg_mailboxid",$this->initid);
      $msg->setValue("emsg_uid",$this->msgStruct["uid"]);
      $msg->setValue("emsg_subject",$this->msgStruct["subject"]);
      $msg->setValue("emsg_from",$this->msgStruct["from"]);
      $msg->setValue("emsg_date",$this->msgStruct["date"]);
      $msg->setValue("emsg_size",$this->msgStruct["size"]);
      $msg->setValue("emsg_textbody",$this->msgStruct["textbody"]==""?' ':$this->msgStruct["textbody"]);

      $ttype=array();
      $tname=array();
      $tos=explode(';',$this->msgStruct["to"]);
      foreach ($tos as $to) {
	if ($to) {
	  $ttype[]='to';
	  $tname[]=$to;
	}
      }
      $tos=explode(';',$this->msgStruct["cc"]);
      foreach ($tos as $cc) {
	if ($cc) {
	  $ttype[]='cc';
	  $tname[]=$cc;
	}
      }
      
      $msg->setValue("emsg_sendtype",$ttype);
      $msg->setValue("emsg_recipient",$tname);
      if (! $msg->isAffected()) $err=$msg->Add();

      if ($err=="") {
	$msg->disableEditControl();
	if (is_array($this->msgStruct["file"])) {
	  // Add attachments files
	  $err=$msg->storeFiles('emsg_attach',$this->msgStruct["file"],$this->msgStruct["basename"]);
	  foreach ($this->msgStruct["file"] as $f) {
	    if (is_file($f)) @unlink($f); // delete temporary files
	  }
	  $this->msgStruct["vid"]=$msg->getTValue('emsg_attach');
	  if ($this->msgStruct["htmlbody"]) $this->msgStruct["htmlbody"]=$this->mb_replacid($this->msgStruct["htmlbody"],$msg->id);
	}

      
      
	$msg->setValue("emsg_htmlbody",$this->msgStruct["htmlbody"]==""?' ':$this->msgStruct["htmlbody"]);


	$err=$msg->Modify();

	if ($err=="") {
	  $destFolder=$this->getdestFolder();
	  $destFolder->addFile($msg->id);	  
	}
      }
      if ($err=="") {
	$headmsg=array("subject"=>$this->msgStruct["subject"],
		       "date"=>$this->msgStruct["date"],
		       "from"=>$this->msgStruct["from"],
		       "msgid"=>$msg->id,
		       "to"=>$this->msgStruct["to"]);
	$headmsg=serialize($headmsg);
	$this->addComment($headmsg,HISTO_NOTICE,"MB_RETREIVE");
      }
  } 
  return $err;
}
/**
 * retrieve unflagged messages from specific folder and sub folders
 * @param int  &$count return number of messages transffered
 */
function mb_recursiveRetrieveMessages(&$count) {
  include_once("FDL/Lib.Dir.php");
  $folder=$this->getValue("mb_folder","INBOX");
  $fdir=$this->fimap.mb_convert_encoding($folder, "UTF7-IMAP","UTF8");
  $folders = imap_list($this->mbox, $fdir, "*");

  $this->mb_retrieveFolderMessages($count); // main folder
  if (count($folders)>0) {
  
    $filter=array();
    $filter[]="smb_mailboxid='".$this->initid."'";
    $subfolders=getChildDoc($this->dbaccess,0,"0","ALL",$filter,1,"TABLE","SUBMAILBOX");
    $subtitle=array();
    if (count($subfolders)>0) {
      foreach ($subfolders as $ids=>$dsub) $subtitle[$dsub["initid"]]=$dsub["smb_path"];
    }
    // unset($folders[0]);
    foreach ($folders as $k=>$subfld) {      
      $isofld=mb_convert_encoding( $subfld, "UTF8", "UTF7-IMAP" ); 
      $f=substr($isofld,mb_strpos($isofld,'}')+1);

      $keysubfolder=array_search($f,$subtitle);
      if (! $keysubfolder) {
	// new sub folder : create it
	$sub=createDoc($this->dbaccess,"SUBMAILBOX");
	$sub->setValue("smb_path",$f);
	$sub->setValue("ba_title",substr($f,strrpos($f,'.')+1));
	$sub->setValue("smb_mailboxid",$this->initid);
	$err=$sub->Add();
	if ($err=="") {
	  $futf7=substr($subfld,strpos($subfld,'}')+1);
	  $pfutf7=substr($futf7,0,strrpos($futf7,'.'));
	  $keypsubfolder=array_search($pfutf7,$subtitle);
	  //	  print "parent [$pfutf7] [$futf7][$keypsubfolder]<br>";
	  if ($keypsubfolder) {
	    $pfld=new_doc($this->dbaccess,$keypsubfolder);
	    $pfld->addFile($sub->initid);
	  } else $this->addFile($sub->initid);
	  $this->setDestFolder($sub->initid);
	  $subtitle[$sub->initid]=$futf7;
	}
      } else {
	$this->destFolder=$subfolders[0];
	$this->setDestFolder($keysubfolder);
      }
      
      $err=$this->mb_retrieveFolderMessages($subcount,$subfld);
     // print "count:$count for $subfld <b>$err</b><br>";	
      $count+=$subcount;
    }
  }
  return $err;
}

/**
 * for RELATED part
 * convert cid: to local href
 * @param string $msg clear text 
 * @param docid $docid id of document message
 * @return string the new text
 */
private function mb_replacid($msg,$docid) {
  $this->msgid=$docid;
    $out = preg_replace(
		      '/"(cid:[^"]+)"/se', 
		      "\$this->mb_cid2http('\\1')",
		      $msg);
    
    return $out;
}

/**
 * 
 * memorize cid references in $this->msgStruct["cid"]
 */
private function mb_getcid($msg) {

  $out = preg_replace('/Content-ID:\s*<([^\s]+)>/sei', 
		      "\$this->mb_putcid('\\1')",
		      imap_body($this->mbox,$msg) );
}

private function mb_putcid($cid) {
  $this->msgStruct["cid"][]=trim($cid);
}

private function mb_cid2http($url) {
  $cid=substr($url,4);
  $key = array_search($cid,$this->msgStruct["cid"] ); 
 
  if (preg_match(PREGEXPFILE,$this->msgStruct["cid"]["key"] , $reg)) {
    $vid=$reg[2];
    $mime=$reg[1];
  }
  $docid=$this->msgid;

  $url=sprintf("?sole=A&app=FDL&action=EXPORTFILE&vid=%d&docid=%d&attrid=emsg_attach&index=%d",
	       $vid,
	       $docid,
	       $key);
  return ('"'.$url.'"');
} 

private function setDestFolder($folderid) {
  $this->destFolderId=$folderid;
}


private function getDestFolder() {
  if (isset($this->destFolder) && $this->destFolder->initid==$this->destFolderId) return $this->destFolder;
  if (! $this->destFolderId) return $this;
  $this->destFolder=new_doc($this->dbaccess,$this->destFolderId);
  return $this->destFolder;
}

function mb_isRecursive() {  
  return ($this->getValue("mb_recursive")=="yes")?MENU_ACTIVE:MENU_INVISIBLE;
}

function maillog($target="_self",$ulink=true,$abstract=false) {
  $tlog=$this->getHisto(true,"MB_RETREIVE",300);
  $tout=array();
  foreach ($tlog as $klog=>$log) {
    $msghead=unserialize($log["comment"]);
    if (is_array($msghead)) {
      $tout[$klog]["rdate"]=strftime("%d/%m/%Y %H:%M",frenchdatetounixts($log["date"]));
      foreach ($msghead as $k=>$v) {
	$tout[$klog][$k]=$v;
      }
      $tout[$klog]["msgdate"]=strftime("%d/%m/%Y %H:%M",strtotime($tout[$klog]["date"]));
    }
  }
  $this->lay->set("title",$this->getDocAnchor($this->id));
  $this->lay->set("today",strftime("%a %d/%m/%Y %H:%M",time()));
  $this->lay->setBlockData("log",$tout);
}
?>
