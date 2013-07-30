<?php
namespace Dcp\Family {
	/** boîte aux lettres  */
	class Mailbox extends \Dcp\Mailconnector\Mailbox { const familyName="MAILBOX";}
	/** dossier de messages  */
	class Submailbox extends Dir { const familyName="SUBMAILBOX";}
	/** message reçu  */
	class Emessage extends Sentmessage { const familyName="EMESSAGE";}
}
namespace Dcp\AttributeIdentifiers {
	/** boîte aux lettres  */
	class Mailbox extends Dir {
		/** [frame] Identification */
		const mb_fr_ident='mb_fr_ident';
		/** [text] login IMAP */
		const mb_login='mb_login';
		/** [password] mot de passe */
		const mb_password='mb_password';
		/** [frame] Paramètre du serveur */
		const mb_fr_server='mb_fr_server';
		/** [text] nom du serveur */
		const mb_servername='mb_servername';
		/** [integer] port */
		const mb_serverport='mb_serverport';
		/** [enum] sécurisé */
		const mb_security='mb_security';
		/** [image] connexion */
		const mb_connectedimage='mb_connectedimage';
		/** [text] dossier d'échange */
		const mb_folder='mb_folder';
		/** [enum] sous dossier ? */
		const mb_recursive='mb_recursive';
		/** [action] Test de connexion */
		const mb_testconnect='mb_testconnect';
		/** [action] Récupérer les messages */
		const mb_retrievemsg='mb_retrievemsg';
		/** [frame] Paramètre des messages */
		const mb_fr_messages='mb_fr_messages';
		/** [docid("EMESSAGE")] Famille des messages */
		const mb_msg_family='mb_msg_family';
		/** [integer("%d minutes")] Délai pour la récupération (minutes) */
		const mb_autoretrieve='mb_autoretrieve';
		/** [enum] Action après récupérations */
		const mb_postaction='mb_postaction';
		/** [text] Dossier pour déplacement */
		const mb_movetofolder='mb_movetofolder';
		/** [enum] Vider la corbeille */
		const mb_purge='mb_purge';
		/** [menu] Voir le journal */
		const mb_viewlog='mb_viewlog';
		/** [menu] Voir l'arborescence */
		const mb_viewfolders='mb_viewfolders';
		/** [menu] Définir les droits sur les messages */
		const mb_profil='mb_profil';
	}
	/** dossier de messages  */
	class Submailbox extends Dir {
		/** [frame] Identification */
		const smb_fr_ident='smb_fr_ident';
		/** [docid] identifiant boîte aux lettres */
		const smb_mailboxid='smb_mailboxid';
		/** [text] boîte aux lettres */
		const smb_mailbox='smb_mailbox';
		/** [text] Chemin d'accès */
		const smb_path='smb_path';
	}
	/** message reçu  */
	class Emessage extends Sentmessage {
		/** [frame] Identification */
		const emsg_fr_ident='emsg_fr_ident';
		/** [text] Identifiant unique */
		const emsg_uid='emsg_uid';
		/** [docid] identifiant boîte aux lettres */
		const emsg_mailboxid='emsg_mailboxid';
		/** [text] boîte aux lettres */
		const emsg_mailbox='emsg_mailbox';
	}
}
