; Script d'installation personnalisé pour ERICKRAPPORT Portable
; @author SOMBINIAINA Erick

!macro preInit
  ; Vérifier si PHP est installé
  ReadRegStr $0 HKLM "SOFTWARE\PHP" "InstallDir"
  ${If} $0 == ""
    ; PHP non trouvé dans le registre, vérifier les chemins courants
    ${If} ${FileExists} "C:\wamp64\bin\php\php8.2.13\php.exe"
      ; WAMP64 trouvé
      MessageBox MB_OK "✅ WAMP64 détecté - ERICKRAPPORT sera configuré automatiquement"
    ${ElseIf} ${FileExists} "C:\wamp\bin\php\php8.2.13\php.exe"
      ; WAMP trouvé
      MessageBox MB_OK "✅ WAMP détecté - ERICKRAPPORT sera configuré automatiquement"
    ${ElseIf} ${FileExists} "C:\xampp\php\php.exe"
      ; XAMPP trouvé
      MessageBox MB_OK "✅ XAMPP détecté - ERICKRAPPORT sera configuré automatiquement"
    ${Else}
      ; Aucun serveur web trouvé
      MessageBox MB_YESNO "⚠️ Aucun serveur web (WAMP/XAMPP) détecté.$\n$\nERICKRAPPORT Portable peut fonctionner sans serveur web externe.$\nVoulez-vous continuer l'installation ?" IDYES continue IDNO abort
      abort:
        Abort "Installation annulée par l'utilisateur"
      continue:
        MessageBox MB_OK "✅ ERICKRAPPORT Portable sera installé avec serveur PHP intégré"
    ${EndIf}
  ${Else}
    MessageBox MB_OK "✅ PHP détecté - ERICKRAPPORT sera configuré automatiquement"
  ${EndIf}
!macroend

!macro customInstall
  ; Créer les dossiers nécessaires
  CreateDirectory "$INSTDIR\uploads"
  CreateDirectory "$INSTDIR\logs"
  CreateDirectory "$INSTDIR\temp"
  
  ; Définir les permissions
  AccessControl::GrantOnFile "$INSTDIR\uploads" "(BU)" "FullAccess"
  AccessControl::GrantOnFile "$INSTDIR\logs" "(BU)" "FullAccess"
  AccessControl::GrantOnFile "$INSTDIR\temp" "(BU)" "FullAccess"
  
  ; Copier les fichiers de configuration
  File "config.php"
  File "composer.json"
  
  ; Message de fin d'installation
  MessageBox MB_OK "🎉 Installation terminée !$\n$\nERICKRAPPORT Portable v2.1.0 est maintenant installé.$\n$\nL'application va démarrer automatiquement."
!macroend

!macro customUnInstall
  ; Nettoyer les fichiers temporaires
  RMDir /r "$INSTDIR\uploads\*.*"
  RMDir /r "$INSTDIR\logs\*.*"
  RMDir /r "$INSTDIR\temp\*.*"
  
  MessageBox MB_OK "🧹 ERICKRAPPORT Portable a été désinstallé avec succès.$\n$\nLes fichiers de données ont été conservés dans le dossier d'installation."
!macroend
