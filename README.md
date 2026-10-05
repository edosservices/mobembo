# ZELVORA

Votre avenir prend la valeur.

Plateforme mobile-first d’investissement immobilier pour la RDC. Les clients s’inscrivent avec un numéro de téléphone, déposent via mobile money, investissent dans des projets, puis demandent un retrait. L’administration valide les preuves, les retraits, les bonus et les distributions.

## Modèle financier

Le solde n’est pas un champ modifié à la main. Chaque mouvement passe par un registre (`ledger_entries`) dans une transaction SQL :

- un dépôt reste **en attente** et n’est crédité qu’après approbation ;
- un investissement débite le solde disponible et augmente le capital investi ;
- le pourcentage d’un projet est un **rendement prévu, estimé et non garanti** ;
- aucun cron ne transforme cette estimation en argent ;
- un revenu n’est crédité que lorsqu’un administrateur enregistre une **distribution réelle** (loyer, vente, autre produit du projet), répartie au prorata ;
- le capital n’est restitué que lors d’une clôture ou d’une annulation explicite ;
- un retrait réserve le montant demandé, calcule les frais, puis attend une décision ;
- un bonus ou un ajustement exige un motif et une ligne d’audit (administrateur, client, ancien solde, nouveau solde, delta, motif, date).

L’inscription d’un filleul ne paie rien. Une commission n’existe que si les paramètres lient le parrainage à une opération réelle (dépôt approuvé ou investissement).

`php artisan zelvora:reconcile` compare chaque solde disponible à la somme du registre.

## Exploitation

ZELVORA n’est pas une banque. Les rendements affichés sont des estimations.

- les pourcentages des plans, les frais de retrait et la commission se modifient dans l’administration ;
- un revenu n’est crédité que par une distribution enregistrée ;
- l’administration peut ouvrir le compte d’un client pour le dépanner, sans effectuer d’opération financière à sa place ;
- le KYC peut être exigé avant un retrait ;
- un fournisseur SMS se branche dans `App\Services\OtpService` si l’OTP doit être exigé (`SMS_DRIVER`).

## Démarrage

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

MySQL est la base prévue. Les tests utilisent SQLite en mémoire.

Comptes créés par le seeder :

| Rôle | Téléphone | Mot de passe |
| --- | --- | --- |
| Administrateur | +243810000001 | valeur de `ADMIN_PASSWORD` (défaut `ChangeMe!Zelvora2026`) |
| Cliente | +243810000002 | `DemoUser!2026` |

Le solde initial de ce compte est une écriture de ledger.

## API mobile

Préfixe `/api/v1`, jeton Bearer Sanctum.

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `GET /me`
- `GET /dashboard`
- `GET /projects`
- `POST /projects/{slug}/invest`
- `GET /investments`
- `POST /deposits` (multipart : montant, moyen, référence, preuve, `idempotency_key`)
- `POST /withdrawals/quote`
- `POST /withdrawals`
- `GET /transactions`
- `GET /notifications`

Les opérations financières exigent une clé `idempotency_key` UUID.
