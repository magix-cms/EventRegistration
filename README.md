# EventRegistration

[![Release](https://img.shields.io/github/release/magix-cms/event-registration.svg)](https://github.com/magix-cms/event-registration/releases/latest)
[![License](https://img.shields.io/github/license/magix-cms/event-registration.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-blue.svg)](https://php.net/)
[![Magix CMS](https://img.shields.io/badge/Magix%20CMS-4.x-success.svg)](https://www.magix-cms.com/)

**EventRegistration** est une extension pour **Magix CMS 4** qui transforme le module d'actualités (*News*) en un système de gestion d'événements avec formulaire d'inscription en ligne. Il permet de définir un quota de participants, de vérifier les disponibilités en temps réel et de notifier instantanément l'administrateur et le visiteur.

## 🚀 Installation

1. Téléchargez et décompressez l'archive du plugin.
2. Placez le dossier `EventRegistration` dans le répertoire `plugins/` de votre installation Magix CMS.
3. Connectez-vous à l'administration de votre site.
4. Rendez-vous dans **Extensions** > **Gestionnaire**.
5. Cliquez sur le bouton d'installation pour **EventRegistration**.

## 🛠 Configuration & Utilisation

Le plugin s'intègre de manière transparente dans l'administration des actualités :

* **Gestion Backend :** Lors de la création ou de la modification d'une actualité dans l'administration, un nouvel onglet **Événement** apparaît. Vous pouvez y activer les inscriptions pour l'actualité concernée et fixer un nombre maximum de participants (ou laisser à 0 pour une capacité illimitée).
* **Affichage Frontend :** Sur les pages d'actualités où les inscriptions sont activées, le formulaire s'affiche automatiquement au bas de l'article via le hook `displayNewsBottom`.
* **Interactions dynamiques :** Si l'événement atteint sa capacité maximale, le formulaire indique automatiquement que l'événement est complet.

## ✨ Fonctionnalités

* **Module d'inscription pour News :** Activez ou désactivez les inscriptions au cas par cas sur chaque article/événement.
* **Gestion des jauges et quotas :** Définition d'un nombre limite d'inscrits avec calcul automatique des places restantes en temps réel.
* **Formulaire AJAX sécurisé :** Soumission fluide sans rechargement de page grâce à `MagixFrontForms`.
* **Intégration conditionnelle avec Google reCAPTCHA v3 :**
    * S'interface avec le plugin `GoogleRecaptcha` via un callback dynamique (`addInjectCondition`).
    * Le script reCAPTCHA n'est injecté dans le `<head>` du module News **que** si l'événement courant autorise les inscriptions, optimisant ainsi le temps de chargement et le score de performance (Core Web Vitals) des autres pages.
* **Notifications par e-mail automatiques :**
    * Envoi d'un e-mail récapitulatif détaillé à l'administrateur du site.
    * Envoi d'un e-mail de confirmation personnalisé au participant.
* **Architecture moderne :** Conçu pour PHP 8.2+ et parfaitement intégré au QueryBuilder et au moteur de templates de Magix CMS 4.

## 📄 Licence

Ce projet est sous licence **GPLv3**. Voir le fichier [LICENSE](LICENSE) pour plus de détails.
Copyright (C) 2008 - 2026 Gerits Aurelien (Magix CMS)
Ce programme est un logiciel libre ; vous pouvez le redistribuer et/ou le modifier selon les termes de la Licence Publique Générale GNU telle que publiée par la Free Software Foundation ; soit la version 3 de la Licence, ou (à votre discrétion) toute version ultérieure.