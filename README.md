# PowerHR - Application de gestion RH et Facturation

## 📝 Description du projet

PowerHR est une application web développée avec Symfony 6.4 dans le cadre du projet de fin d'études à **Esprit School of Engineering**.  
Elle permet de gérer les employés, les pointages, la paie, les demandes internes, ainsi que la facturation et les paiements clients/fournisseurs.

## 🔖 Topics

`symfony` `web-development` `gestion-paie` `pointage` `facturation` `pdf-generation` `csv-import` `Esprit School of Engineering`

## 🗂️ Table des matières

- [Installation](#installation)
- [Utilisation](#utilisation)
- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Contribution](#contribution)
- [Licence](#licence)

---

## ⚙️ Installation

1. Cloner le dépôt GitHub :
```bash
git clone https://github.com/votre-utilisateur/powerHR.git
cd powerHR
```

2. Installer les dépendances PHP et JS :
```bash
composer install
```

3. Configurer `.env.local` :
```
DATABASE_URL="mysql://user:pass@127.0.0.1:3306/powerhr"
```

4. Créer la base de données et charger les fixtures :
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

---

## 🚀 Utilisation

Lancer le serveur Symfony :
```bash
symfony server:start
```

Accéder à l’application via `http://127.0.0.1:8000`.

---

## 🌟 Fonctionnalités

- 🔐 Authentification sécurisée avec rôles (`Directeur`, `Charges`, `Facturation`, `Ouvrier`, `Admin`)
- 🕒 Gestion des pointages (manuel + import CSV)
- 📅 Calcul automatique de la paie selon les jours travaillés
- 🧾 Génération des fiches de paie en PDF
- 📦 Gestion des articles, factures et paiements
- 💵 Paiement en ligne avec flouci
- 📬 Système de feedbacks clients/fournisseurs
- ❓ Système de questionnaire, response et demande
- 🏢 Gestion des entreprises et departements



---

## 🛠 Technologies utilisées

- **Backend** : Symfony 6.4 (PHP 8.2), Doctrine ORM
- **Frontend** : Twig, Bootstrap, JS
- **PDF** : DomPDF
- **Import** : CSV parsing natif PHP
- **Base de données** : MySQL
- **Outils** : Trello, GitHub, Postman, Flouci

---

## 🤝 Contribution

Les contributions sont les bienvenues !  
1. Fork le projet  
2. Crée une branche (`git checkout -b feature/AjoutX`)  
3. Commit (`git commit -am 'Ajout de la fonctionnalité X'`)  
4. Push (`git push origin feature/AjoutX`)  
5. Ouvre une Pull Request

---

## 📄 Licence

Ce projet est sous licence MIT.  
Tu peux librement l’utiliser, le modifier, et le redistribuer à condition de conserver la mention de l’auteur original.

---

## 💼 Remerciements

Projet développé sous la supervision de **Esprit School of Engineering** dans le cadre du module PFE - 2025.