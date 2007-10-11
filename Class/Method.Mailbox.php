<?php

public $mbox;
function specRefresh() {
 
  $err=$this->mb_testConnection();
  return $err;

}
function mb_testConnection() {
  include_once("FDL/Lib.Vault.php");
  $login=$this->getValue("mb_login");
  $password=$this->getValue("mb_password");
  $server=$this->getValue("mb_servername");
  $port=$this->getValue("mb_serverport");
  $ssl=$this->getValue("mb_security");


  if ($ssl=="SSL") $fimap=sprintf("{%s:%d/imap/ssl/novalidate-cert}INBOX",$server,$port);
  else $fimap=sprintf("{%s:%d}INBOX.Test",$server,$port);
  imap_timeout(1,5); // 5 seconds
  $this->mbox = @imap_open($fimap,$login ,$password );
  if (!$this->mbox) {
    $err=imap_last_error();
    $this->setValue("mb_connectedimage","mailbox_red.png");
  } else {
    $this->setValue("mb_connectedimage","mailbox_green.png");    
    echo "<h1>Mailboxes</h1>\n";
    $folders = imap_listmailbox($this->mbox, $fimap, "*");

    if ($folders == false) {
      echo "Appel echoue<br />\n";
    } else {
      foreach ($folders as $val) {
        echo $val . "<br />\n";
      }
    }

    echo "<h1>headers dans INBOX</h1>\n";
    $headers = imap_headers($this->mbox);

    if ($headers == false) {
      echo "Appel echoue<br />\n";
    } else {
      foreach ($headers as $val) {
        echo "[$val]" . "<br />\n";
      }
    }

    //  $msgs = imap_sort($this->mbox,SORTDATE,1 );
    $msgs = imap_search($this->mbox,'UNFLAGGED' );
    if (is_array($msgs)) {
      foreach ($msgs as $k=>$val) {
	$this->mb_parseMessage($val);
	//print_r2( imap_fetchheader($this->mbox,$val));
      }
    } else {
      echo "imap_list a echoue : " . imap_last_error() . "\n";
    }
    imap_close($this->mbox);
  }

  return $err;

}

/**
 * decode headers text
 * @param string $s encoded text
 * @return string iso8859-1 text
 */
static function mb_decode($s) {
 $t=imap_mime_header_decode($s);
 $ot='';
 foreach ($t as $st) {
   $ot.=$st->text;
 }
 return $ot;
}


function mb_parseMessage($msg) {
  print "<hr>";
  
  $h= imap_header($this->mbox,$msg);
  $uid=$h->message_id;
  print "<b>".$this->mb_decode($h->subject)."</b> [$uid]";
  
  if ($uid=="") $uid=$h->date.'-'.$h->Size;
  // print_r2($h);

  
  //	print("<b>$body</b>");
  $this->msgStruct=array();
   $this->msgStruct["subject"]=$this->mb_decode($h->subject);
   $this->msgStruct["uid"]=$uid;
   $this->msgStruct["date"]=$h->date;
   $this->msgStruct["to"]=$this->mb_implodemail($h->to);
   $this->msgStruct["from"]=$this->mb_implodemail($h->from);
   $this->msgStruct["cc"]=$this->mb_implodemail($h->cc);
   $this->msgStruct["size"]=$h->Size;


   $status = imap_clearflag_full($this->mbox, $msg, "\\Seen");
   // $status = imap_setflag_full($this->mbox, $msg, '\\Flagged');
   $status = imap_setflag_full($this->mbox, $msg, '$label3');


   $o=imap_fetchstructure($this->mbox,$msg);
   //  print_r2($o);
   if ($o->subtype=="PLAIN") {
     $body=imap_body($this->mbox,$msg);
     $this->msgStruct["textbody"]=$body;
   } else  if ($o->subtype=="HTML") {
     $body=imap_body($this->mbox,$msg);
     $this->msgStruct["htmlbody"]=$body;
     
   
   } else  if ($o->subtype=="ALTERNATIVE") {
     if ($o->parts[0]->subtype=="PLAIN") {
       $body1=imap_fetchbody($this->mbox,$msg,'1');
       $this->msgStruct["textbody"]=$body1;
     }
     if ($o->parts[1]->subtype=="HTML") {
       $body=imap_fetchbody($this->mbox,$msg,'2');
       $this->msgStruct["htmlbody"]=$body;       
     } else if ($o->parts[1]->subtype=="RELATED") {
       $this->mb_getmultipart($o->parts[1],$msg,'2.');
     } else if ($o->parts[1]->subtype=="MIXED") {
       $this->mb_getmultipart($o->parts[1],$msg,'2.');
     }
   } else if ($o->subtype=="MIXED") {
     $this->mb_getmultipart($o,$msg);
    
   } else if ($o->subtype=="RELATED") {
     $this->mb_getmultipart($o,$msg);
   }


   $this->mb_getcid($msg);
   $this->mb_createMessage();

}

function mb_getmultipart($o,$msg,$chap="") {
   foreach ($o->parts as $k=>$part) {

     //     print "<ul><b>".sprintf("$chap%d",$k+1)."</b></ul>";
       if ($part->subtype=="PLAIN") {
	 $body=imap_fetchbody($this->mbox,$msg,sprintf("$chap%d",$k+1));
	 $this->msgStruct["textbody"]=$body;
       } else  if ($part->subtype=="HTML") {
	 $body=imap_fetchbody($this->mbox,$msg,sprintf("$chap%d",$k+1));
	 switch ($part->encoding) {
	 case 4: // QUOTED-PRINTABLE
	     $body=quoted_printable_decode($body);
	     break;
	 }	 
	 $this->msgStruct["htmlbody"]=$body;       
       } else {
	 if (($part->disposition=="INLINE")||($part->disposition=="ATTACHMENT")) {

	   print "<h1>".sprintf("$chap%d",$k+1)."</h1>";
	   $body=imap_fetchbody($this->mbox,$msg,sprintf("$chap%d",$k+1));
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
	   $basename="";
	   if ($part->ifdparameters) {
	     foreach ($part->dparameters as $param) {
	       if ($param->attribute=="FILENAME") $basename=basename($param->value);
	     }
	   }
	   if ($part->ifparameters) {
	     foreach ($part->parameters as $param) {
	       if ($param->attribute=="NAME") $cid=$param->value;
	     }
	   }
	   $filename=uniqid("/var/tmp/_fdl").'.'.strtolower($part->subtype);
	   $nc=file_put_contents($filename,$body);
	   $this->msgStruct["file"][]=$filename;
	   $this->msgStruct["basename"][]=$basename;
	   // $this->msgStruct["cid"][]=$cid;
	 } else  if ($part->subtype=="RELATED") {
	   print "<b>RELATED BIS</b><br>";
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
    if (isset($v->personal)) $email=$v->personal.' <'.$email.'>';    
    $tmail[$k]=$email;
  }
  return implode(";",$tmail);
}


function mb_createMessage() {
  include_once("FDL/Lib.Dir.php");

  $uid=pg_escape_string($this->msgStruct["uid"]);
  $filter[]="emsg_uid='$uid'";
  $tdir=getChildDoc($this->dbaccess,0,"0",1,$filter,1,"LIST","EMESSAGE");
  if (count($tdir)==0) {
    $msg=createdoc($this->dbaccess,"EMESSAGE");    
  } else {
    $msg=$tdir[0];
  }
  //  print_r2($this->msgStruct);
  if ($msg) {
      $msg->setValue("emsg_mailboxid",$this->id);
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
	  $this->addFile($msg->id);
	}
      }
  } 
}


function mb_replacid($msg,$docid) {
  $this->msgid=$docid;
    $out = preg_replace(
		      '/"(cid:[^"]+)"/se', 
		      "\$this->mb_cid2http('\\1')",
		      $msg);
    
    return $out;
}
function mb_getcid($msg) {

  $out = preg_replace('/Content-ID:\s*<([^\s]+)>/sei', 
		      "\$this->mb_putcid('\\1')",
		      imap_body($this->mbox,$msg) );
}

function mb_putcid($cid) {
  $this->msgStruct["cid"][]=trim($cid);
}

function mb_cid2http($url) {
  $cid=substr($url,4);
  $key = array_search($cid,$this->msgStruct["cid"] ); 
 
  if (ereg ("(.*)\|(.*)",$this->msgStruct["cid"]["key"] , $reg)) {
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
?>