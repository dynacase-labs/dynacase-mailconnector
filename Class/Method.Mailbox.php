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
  else $fimap=sprintf("{%s:%d}INBOX",$server,$port);
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
      echo "Appel échoué<br />\n";
    } else {
      foreach ($folders as $val) {
        echo $val . "<br />\n";
      }
    }

    echo "<h1>headers dans INBOX</h1>\n";
    $headers = imap_headers($this->mbox);

    if ($headers == false) {
      echo "Appel échoué<br />\n";
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
      echo "imap_list a échoué : " . imap_last_error() . "\n";
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

   $struct["subject"]=$this->mb_decode($h->subject);
   $struct["uid"]=$uid;
   $struct["date"]=$h->date;
   $struct["to"]=$this->mb_implodemail($h->to);
   $struct["from"]=$this->mb_implodemail($h->from);
   $struct["cc"]=$this->mb_implodemail($h->cc);
   $struct["size"]=$h->Size;


   $status = imap_clearflag_full($this->mbox, $msg, "\\Seen");
   // $status = imap_setflag_full($this->mbox, $msg, '\\Flagged');
   $status = imap_setflag_full($this->mbox, $msg, '$label3');


   $o=imap_fetchstructure($this->mbox,$msg);
   print_r2($o);
   if ($o->subtype=="PLAIN") {
     $body=imap_body($this->mbox,$msg);
     $struct["textbody"]=$body;
   } else  if ($o->subtype=="HTML") {
     $body=imap_body($this->mbox,$msg);
     $struct["htmlbody"]=$body;
     
   
   } else  if ($o->subtype=="ALTERNATIVE") {
     if ($o->parts[0]->subtype=="PLAIN") {
       $body1=imap_fetchbody($this->mbox,$msg,'1');
       $struct["textbody"]=$body1;
     }
     if ($o->parts[1]->subtype=="HTML") {
       $body=imap_fetchbody($this->mbox,$msg,'2');
       $struct["htmlbody"]=$body;       
     } else if ($o->parts[1]->subtype=="RELATED") {
       $this->mb_getmultipart($o->parts[1],$msg,$struct,'2.');
     } else if ($o->parts[1]->subtype=="MIXED") {
       $this->mb_getmultipart($o->parts[1],$msg,$struct,'2.');
     }
   } else if ($o->subtype=="MIXED") {
     $this->mb_getmultipart($o,$msg,$struct);
    
   } else if ($o->subtype=="RELATED") {
     $this->mb_getmultipart($o,$msg,$struct);
   }


   $this->mb_createMessage($struct);

}

function mb_getmultipart($o,$msg,&$struct,$chap="") {
   foreach ($o->parts as $k=>$part) {

     print "<ul><b>".sprintf("$chap%d",$k+1)."</b></ul>";
       if ($part->subtype=="PLAIN") {
	 $body=imap_fetchbody($this->mbox,$msg,sprintf("$chap%d",$k+1));
	 $struct["textbody"]=$body;
       } else  if ($part->subtype=="HTML") {
	 $body=imap_fetchbody($this->mbox,$msg,sprintf("$chap%d",$k+1));
	 switch ($part->encoding) {
	 case 4: // QUOTED-PRINTABLE
	     $body=quoted_printable_decode($body);
	     break;
	 }	 
	 $struct["htmlbody"]=$body;       
       } else {
	 if (($part->disposition=="INLINE")||($part->disposition=="ATTACHMENT")) {
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
	   $filename=uniqid("/var/tmp/_fdl").'.'.strtolower($part->subtype);
	   $nc=file_put_contents($filename,$body);
	   $struct["file"][]=$filename;
	   $struct["basename"][]=$basename;;
	 } else  if ($part->subtype=="RELATED") {
	   print "<b>RELATED BIS</b><br>";
	   $this->mb_getmultipart($part,$msg,$struct,sprintf("$chap%d.",$k+1));
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


function mb_createMessage($msgStruct) {
  include_once("FDL/Lib.Dir.php");
  $uid=pg_escape_string($msgStruct["uid"]);
  $filter[]="emsg_uid='$uid'";
  $tdir=getChildDoc($this->dbaccess,0,"0",1,$filter,1,"LIST","EMESSAGE");
  if (count($tdir)==0) {
    $msg=createdoc($this->dbaccess,"EMESSAGE");    
  } else {
    $msg=$tdir[0];
  }
  //  print_r2($msgStruct);
  if ($msg) {
      $msg->setValue("emsg_mailboxid",$this->id);
      $msg->setValue("emsg_uid",$msgStruct["uid"]);
      $msg->setValue("emsg_subject",$msgStruct["subject"]);
      $msg->setValue("emsg_from",$msgStruct["from"]);
      $msg->setValue("emsg_date",$msgStruct["date"]);
      $msg->setValue("emsg_size",$msgStruct["size"]);
      $msg->setValue("emsg_textbody",$msgStruct["textbody"]==""?' ':$msgStruct["textbody"]);
      $msg->setValue("emsg_htmlbody",$msgStruct["htmlbody"]==""?' ':$msgStruct["htmlbody"]);
      $ttype=array();
      $tname=array();
      $tos=explode(';',$msgStruct["to"]);
      foreach ($tos as $to) {
	if ($to) {
	  $ttype[]='to';
	  $tname[]=$to;
	}
      }
      $tos=explode(';',$msgStruct["cc"]);
      foreach ($tos as $cc) {
	if ($cc) {
	  $ttype[]='cc';
	  $tname[]=$cc;
	}
      }
      
      $msg->setValue("emsg_sendtype",$ttype);
      $msg->setValue("emsg_recipient",$tname);


      if (is_array($msgStruct["file"])) {
	// Add attachments files
	$err=$msg->storeFiles('emsg_attach',$msgStruct["file"],$msgStruct["basename"]);
	foreach ($msgStruct["file"] as $f) {
	  if (is_file($f)) @unlink($f); // delete temporary files
	}
      }

      if ($msg->isAffected()) $err=$msg->Modify();
      else $err=$msg->Add();
      if ($err=="") {
	$this->addFile($msg->id);
      }
  }

  
  
}

?>