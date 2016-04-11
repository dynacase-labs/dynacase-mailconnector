<?php
/*
 * @author Anakeen
 * @package MAILCONNECTOR
*/

function getimapfolders($mboxid)
{
    $err = "";
    $tr = array();
    /**
     * @var \Dcp\Family\Mailbox $mb
     */
    $mb = new_doc("", $mboxid);
    if ($mb->isAlive()) {
        if ($mb->doctype === "C") return _("Can't find folder in documents creation, save document before choosing folder");
        $err = $mb->mb_connection();
        if ($err == "") {
            $list = imap_list($mb->mbox, $mb->fimap, "*");
            //      print_r2($list);
            sort($list);
            foreach ($list as $k => $fld) {
                $fld = mb_convert_encoding($fld, "UTF8", "UTF7-IMAP");
                //print "|$fld]\n<br>";
                //	$fld=$mb->imap_utf7_decode_zero($fld);
                $f = substr($fld, strpos($fld, '}') + 1);
                
                $tr[] = array(
                    $f,
                    $f
                );
            }
        }
    }
    
    if ($err) return $err;
    return $tr;
}
?>
