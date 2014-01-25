��    4      �  G   \      x  	   y     �  y   �  �       �     �     �     �  "   �     	  Y   '	  @   �	  R   �	  J   
  	   `
     j
     w
     �
  �   �
  �   7  �                     G   �     �  :   �  &   9     `     h     v     �  
   �     �     �  	   �  o   �  �     "   �  %   �  8     k   ?  1   �  "   �                  =     ^     m  �   q  %   �         2     :  }   X  �  �     |     �     �     �     �     �  k   �  D   N  g   �  c   �     _     w     �     �  �   �  �   S  �   7       
   1  �   <  R   �     -  O   L  #   �     �     �     �     �     �                    g      �   �   2   G!  0   z!      �!  w   �!  <   D"  3   �"  %   �"  #   �"  *   �"     *#     ?#  �   C#  *   �#                                &   #            1   !                       )   -                 .                    +   4           3                ,                '          0      *      	          
           "   2          /   %   (   $             version  (This user is locked out) 2-factor authentication has been deactivated for your account. If you want to reactivate it, go to your %sprofile page%s. <h2>Authorized Clock Desynchronization</h2><p>First of all, you have to understand how the 2-factor authentication works.</p><p>The Google Authenticator will generate a TOTP which stands for Time based One Time Pasword. This one time password, as you might now understand, is generated based on the current time.</p><p>If the server's (where your site is hosted) clock and the user's phone clock are not perfectly synchronized, the one time password generated won't work, as it will be generated on a time which is different from the server.</p><p>The authorized desynchronization will allow your users more time to use their one time password. By default, one code will be valid for <strong>30 seconds</strong>. If you want to give them more time, you can specify a delay in <strong>minutes</strong>.</p><p>Of course, if you give users more time, the security will be lowered. It is advised to stick with the default 30 secs.</p> Account password Activate Activate Plugin Authenticator Authorized Clock Desynchronization Desynchronization Do you want to force your users to use 2-factor authentication (admins AND you included)? Do you wish to enable the 2-factor authentication for this site? Do you wish to use 2-factor authentication (require the Google Authenticator app)? For security reasons, please type your password to see your recovery code. Force Use Generate Key Get QR Code Google Authenticator If you are unable to use the Google Authenticator for any reason, you can use this one time recovery code instead of the TOTP. Save this code in a safe place. If you chose to force users to use 2-factor authentication, you can specify a maximum number of times a user can login WITHOUT setting up the 2-factor authentication (leave <code>0</code> for unlimited attempts). If you do not have configured the 2-factor authentication,<br> just leave this field blank and you will be logged-in as usual.<br><br>If you can't use the Google Authenticator app for whatever reason,<br>you can use your recovery code instead. Login Attempts Max Attempts Must be in <code>min</code> (&plusmn;). Avoid invalid one-time passwords issues. Please read the contextual help for more info. Name under which this site will appear in the Google Authenticator app. No recovery code set yet. Number of times the user logged-in without using the TOTP. Please provide your one time password. QR Code Recovery Code Regenerate Key Reset Revoke Key Secret Show Site Name The Google Authenticator one time password is incorrect or expired. Please try with a newly generated password. The admin is requesting all users to activate 2-factor authentication. <a href="%s">Please do it now</a>. You only have <strong>%s</strong> login attempts left. The attempts count has been reset. The key for user %s has been revoked. The one time password you used has already been revoked. This is going to be your secret key. Please save changes and scroll back to this field to get your QR code. This is your personal secret key. Don't share it! This user didn't set a secret key. This user has a secret key. WP Google Authenticator Settings Write this down and keep it safe Wrong password Yes You have reached the maximum number of logins WITHOUT using 2-factor authentication. Please contact the admin to reset your account. Your secret key has been regenerated. Project-Id-Version: Google Authenticator for WordPress
POT-Creation-Date: 2014-01-25 12:58+0700
PO-Revision-Date: 2014-01-25 13:02+0700
Last-Translator: Julien Liabeuf <julien@liabeuf.fr>
Language-Team: Julien Liabeuf <julien@liabeuf.fr>
Language: en
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
X-Generator: Poedit 1.6.3
X-Poedit-KeywordsList: _;gettext;gettext_noop;__;_e;_n
X-Poedit-Basepath: .
X-Poedit-SearchPath-0: C:\wamp\www\plugins-dev\wp-content\plugins\wp-google-authenticator
 version (Cet utilisateur est bloqué) La validation en 2 étapes à été désactivée pour votre compte. Pour la réactiver, veuillez aller dans %svotre profil%s. <h2>Désynchronisation autorisée</h2><p>Tout d'abord, vous devez comprendre comment l'autentification en 2 étapes fonctionne.</p><p>L'application Google Authenticator va générer un TOTP, ce qui signifie "Time Based One Time Password", ou mot de passe unique basé sur le temps. Ce mot de passe unique, comme vous devez le comprendre maintenant, est généré en fonction de la date exacte au moment de la génération.</p><p>Si l'horloge du serveur (où votre site est hébergé) n'est pas en synchronisation parfaite avec cette du téléphone utilisé, le mot de passe unique ne fonctionnera pas puisque généré à partir d'une date différente de cette du serveur (quelques minutes de différence par exemple).</p><p>La désynchronisation autorisée permet d'étendre le temps de validité d'un mot de passe unique. Par défaut, un code unique est valide pendant <strong>30 secondes</strong> après la génération. Si vous souhaitez donner plus de temps à vos utilisateurs, vous pouvez spécifier un délai <strong>en minutes</strong>.</p><p>Evidemment, si vous donnez un délai plus long, la sécurité sera réduite. Il est conseillé de garder les 30 secondes par défaut.</p> Mot de passe Activer Activer le plugin Authenticator Désynchronisation autorisée Désynchronisation Souhaitez-vous forcer l'utilisation de l'autentication en 2 étapes (ce qui inclus les admins, dont vous) ? Souhaitez-vous utiliser l'autentification en 2 étapes sur ce site ? Souhaitez-vous utiliser l'autentification en 2 étapes (nécessite l'application Google Authenticator). Par mesure de sécurité, vous devez entrer votre mot de passe pour voir le code de récupération. Forcer les utilisateurs Générer une clé Voir QR Code Google Authenticator Si vous n'êtes pas en mesure d'utiliser l'application Google Authenticator, vous pouvez utiliser ce mot de passe de récupération unique. Conservez le en lieu sûr. Si vous souhaitez forcer les utilisateur à utiliser l'autentification en 2 étapes, vous pouvez spécifier un nombre maximum d'autentifications SANS utiliser les 2 étapes (laissez <code>0</code> pour ne pas mettre de limite). Si vous n'avez pas configuré l'autentification en 2 étapes, <br> laissez ce champ vide.<br><br>Si vous ne pouvez pas utiliser l'application Google Authenticator,<br>vous pouvez utiliser votre code de récupération à la place. Tentatives de login Tentatives Doit être en <code>min</code> (&plusmn;).  Permer d'éviter les mots de passes uniques invalides. Merci de lire l'aide contextuelle pour plus d'information. Nous sous lequel ce site doit apparaître dans l'application Google Authenticator. Pas de code de récupération. Nombre de fois où l'utilisateur s'est identifié sans utiliser de code unique. Merci de fournir votre code unique. QR Code Code de récupération Regénérer la clé RAZ Annuler la clé Secret Voir Nom du site Le mot de passe unique Google Authenticator est incorrect ou à expiré. Merci d'en essayer un nouveau. L'administrateur oblige les utilisateurs à utiliser l'autentification en 2 étapes. <a href="%s">Veuillez configurer votre clé</a>. Il ne vous reste que <strong>%s</strong> identifications. Le compteur de tentatives à été remis à zéro. La clé pour l'utilisateur %s à été annulée. Ce code unique à été annulé. Ceci est votre clé secrète. Veuillez enregistrer les changment puis revenir à ce champ afin d'obtenir votre QR code. Ceci est votre clé secrète. Ne la partagez avec personne ! Cet utilisateur n'a pas généré de clé secrète. Cet utilisateur à une clé secrète. Paramètres WP Google Authenticator Notez ce code et conservez le en lieu sûr Mot de passe erroné Oui Vous avez atteint le nombre maximum d'identifications SANS utiliser la validation en 2 étapes. Veuillez contacter l'administrateur qui pourra débloquer votre compte. Votre clé secrète à été regénérée. 