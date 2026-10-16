# CVora

Votre talent mérite le meilleur CV.

CVora est une application Laravel pour créer, relire, importer et exporter des CV et des lettres de motivation. L’assistant d’IA reformule uniquement les faits fournis et n’enregistre rien sans validation. Les prix et les quotas se règlent dans l’administration.

Cette copie est dans le dossier `cvora/` du dépôt, à côté de l’application existante. Le dépôt GitHub `edosservices/Cvora` n’était pas accessible en écriture depuis cet environnement.

## Prérequis

- PHP 8.4 avec `mbstring`, `xml`, `curl`, `mysql`, `sqlite`, `zip`, `gd`, `intl`
- Composer 2
- MySQL ou MariaDB en production
- Node.js 22 pour compiler les assets

## Installation

```bash
cd cvora
composer install
cp .env.example .env
php artisan key:generate
```

Renseignez MySQL dans `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cvora
DB_USERNAME=cvora
DB_PASSWORD=
```

Pour un essai local sans MySQL, utilisez SQLite :

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Puis :

```bash
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

L’application répond sur `http://127.0.0.1:8000`.

Créez l’administrateur avec des valeurs qui ne sont pas commitées :

```env
ADMIN_NAME="Administrateur CVora"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=un-mot-de-passe-long
```

Relancez `php artisan db:seed` après avoir renseigné ces variables. Aucun mot de passe d’administration n’est codé en dur.

## Offres

Trois offres sont créées : Gratuit, Premium et Pro. Leurs quotas sont en base. Les prix Premium et Pro sont vides tant qu’un administrateur ne les saisit pas. Un prix vide ne peut pas être encaissé. L’offre gratuite coûte 0.

L’offre gratuite permet un CV, les modèles Moderne et Classique, un aperçu et un petit quota d’IA. Elle n’inclut ni export, ni import, ni lettre. Ces limites se modifient dans `/admin/offres`.

## Intelligence artificielle

Sans `AI_API_KEY`, l’assistant affiche qu’il n’est pas configuré. Il ne fabrique pas de texte à la place du modèle.

```env
AI_API_KEY=
AI_BASE_URL=https://api.openai.com/v1
AI_MODEL=gpt-4o-mini
```

Toute API compatible avec `POST /chat/completions` convient. L’administrateur peut surcharger le modèle et l’URL de base. La clé reste dans l’environnement.

Les textes de CV et d’offres sont envoyés comme données, pas comme instructions. Une proposition s’affiche avant d’être recopiée par la personne. Le CV n’est pas modifié en silence.

## Paiements

```env
PAYMENT_MODE=sandbox
SANDBOX_WEBHOOK_SECRET=
```

Le bac à sable ne débite rien. Une offre payante ne s’active qu’après une notification dont la signature HMAC correspond au secret. En production, le bac à sable est refusé.

UniPay n’est pas simulé. Il faut les quatre variables, puis `PAYMENT_MODE=unipay` :

```env
UNIPAY_BASE_URL=
UNIPAY_MERCHANT_ID=
UNIPAY_API_KEY=
UNIPAY_WEBHOOK_SECRET=
```

Le client actuel appelle `POST {UNIPAY_BASE_URL}/api/v1/payments` et attend `payment_url` et `provider_reference`. La notification doit reprendre la référence, le statut `paid` et le montant en centimes, signée dans `X-Payment-Signature`. Adaptez `App\Services\Billing\UnipayPaymentGateway` au contrat réel du marchand avant de l’annoncer comme opérationnel.

## Import et export

PDF via Dompdf, DOCX via PHPWord. L’import PDF utilise un extracteur de texte. Un PDF scanné sans texte est signalé. Aucun OCR n’est branché. Les fichiers importés ne sont pas exécutés et restent hors du dossier public.

## Compte et téléphone

L’inscription se fait par e-mail. La vérification d’e-mail existe et n’est exigée que si l’administrateur active `Exiger la vérification e-mail`. La colonne `users.phone` est prête pour une connexion par téléphone ultérieure. Aucun code SMS n’est envoyé.

## Tests

```bash
cd cvora
php artisan test
```

Les tests utilisent SQLite en mémoire. Les appels d’IA et les paiements sont simulés localement.

## Déploiement

- `APP_ENV=production`, `APP_DEBUG=false`
- `php artisan migrate --force --seed` sur une base vide, ou sans `--seed` si les offres existent déjà
- `npm run build`
- `php artisan storage:link`
- planifier rien de particulier : aucun cron ne crédite d’argent
- servir `public/` comme racine web
- ne pas commiter `.env`

## Ce qui fonctionne

- Site public, authentification Breeze, rôles utilisateur et administrateur
- Éditeur de CV, huit mises en page, aperçu, duplication, historique selon l’offre
- Export PDF et DOCX selon l’offre
- Import PDF et DOCX avec confirmation, y compris le refus d’écraser un CV sans case cochée
- Lettres, candidatures suivies à la main, offres collées
- Assistant IA lorsqu’une clé valide est présente
- Offres, quotas et paiement par notification signée
- Administration des comptes, tarifs, modèles, drapeaux et journaux techniques

## Ce qui demande encore une configuration externe

- La clé du fournisseur d’IA
- Le contrat marchand UniPay réel, si le format d’API diffère de l’adaptateur prévu
- Un service OCR pour les PDF scannés
- L’envoi SMS pour une future connexion par téléphone
- L’identité d’éditeur à compléter dans les mentions légales avant une ouverture publique
