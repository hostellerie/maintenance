<?php

$LANG_configsections['maintenance'] = array('label' => 'Maintenance', 'title' => 'Configuration du plugin Maintenance');
$LANG_confignames['maintenance'] = array(
    'enabled' => 'Activer le mode maintenance',
    'message' => 'Message en texte brut affiché aux visiteurs'
);
$LANG_configsubgroups['maintenance'] = array('sg_0' => 'Paramètres principaux');
$LANG_fs['maintenance'] = array('fs_01' => 'Paramètres du plugin Maintenance');
$LANG_tab['maintenance'] = array('tab_main' => 'Paramètres principaux');
$LANG_configselects['maintenance'] = array(
    0 => array('Vrai' => 1, 'Faux' => 0),
    1 => array('Activé' => 1, 'Désactivé' => 0)
);
$LANG_MAINTENANCE = array(
    'plugin_name'     => 'Maintenance',
    'page_title'      => 'Site en maintenance',
    'default_message' => 'Le site est actuellement en maintenance. Merci de revenir plus tard.',
    'admin_notice'    => 'Le mode maintenance est actif. Les administrateurs autorisés peuvent continuer à utiliser le site.',
    'login_summary'   => 'Accès autorisé',
    'login_name'      => 'Identifiant',
    'login_password'  => 'Mot de passe',
    'login_submit'    => 'Se connecter',
    'admin_title'     => 'Administration de la maintenance',
    'admin_intro'     => 'Le mode maintenance bloque les requêtes Geeklog normales avant le rendu de la page.',
    'status_enabled'  => 'Le mode maintenance est actuellement activé.',
    'status_disabled' => 'Le mode maintenance est actuellement désactivé.',
    'open_configuration' => 'Ouvrir la configuration',
    'documentation'   => 'Fonctionnement',
    'documentation_text' => 'Les utilisateurs autorisés contournent le mode maintenance. Les requêtes bloquées reçoivent HTTP 503 et Retry-After: 3600. La page publique ne contient aucun formulaire de connexion. Les opérateurs utilisent l URL de connexion Maintenance séparée, qui délègue l authentification à Geeklog.',
    'login_url_title' => 'URL de connexion autorisée',
    'login_url_help' => 'Enregistrez cette URL opérationnelle privée avant d\'activer le mode maintenance. Elle est volontairement absente de la page publique.',
    'login_url_bookmarked' => 'J\'ai enregistré cette URL dans mes favoris.',
    'email_login_url' => 'M\'envoyer cette URL de connexion à l\'adresse e-mail de mon compte.',
    'email_submit' => 'Envoyer l\'e-mail',
    'email_subject' => 'URL d\'accès à la maintenance de %s',
    'email_body' => "Bonjour %s,\n\nVoici l'URL de connexion autorisée pour la maintenance de %s :\n%s\n\nConservez cette URL de manière confidentielle.",
    'email_sent' => 'L\'URL de connexion a été envoyée à l\'adresse e-mail de votre compte.',
    'email_failed' => 'Geeklog n\'a pas pu envoyer l\'e-mail. Vérifiez la configuration de la messagerie et les journaux.',
    'email_missing' => 'Votre compte Geeklog ne possède pas d\'adresse e-mail.',
    'email_checkbox_required' => 'Cochez l\'option d\'envoi par e-mail avant de valider.',
    'email_token_error' => 'Le jeton de sécurité a expiré. Veuillez réessayer.'
);
