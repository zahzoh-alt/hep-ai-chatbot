# Guide d'intégration HEP Chatbot dans Moodle
# HEP Chatbot – Moodle Integration Guide

---

## 🇫🇷 GUIDE D'INTÉGRATION MOODLE (FRANÇAIS)

### Prérequis
- Accès administrateur Moodle
- Clé API Anthropic (https://console.anthropic.com)
- Moodle version 3.9 ou supérieure

---

### MÉTHODE 1 – Bloc HTML Moodle (Recommandée, la plus simple)

#### Étape 1 : Obtenir votre clé API Anthropic
1. Rendez-vous sur https://console.anthropic.com
2. Créez un compte ou connectez-vous
3. Allez dans **API Keys** → **Create Key**
4. Copiez la clé (commence par `sk-ant-...`)

#### Étape 2 : Préparer le fichier HTML
1. Ouvrez le fichier `hep-chatbot.html`
2. Trouvez la ligne : `const API_KEY_PLACEHOLDER = 'YOUR_ANTHROPIC_API_KEY';`
3. Remplacez par votre vraie clé : `const API_KEY_PLACEHOLDER = 'sk-ant-VOTRE_CLE';`
4. Sauvegardez le fichier

#### Étape 3 : Héberger les fichiers
**Option A – Fichiers dans Moodle (recommandé)**
1. Admin Moodle → **Gestion du site** → **Serveur** → **Fichiers du site**
2. Uploadez `hep-chatbot.html`
3. Notez l'URL du fichier

**Option B – Hébergement externe**
1. Uploadez sur votre serveur web (ex: `/var/www/html/hep-chatbot/`)
2. Assurez-vous que le fichier est accessible en HTTPS

#### Étape 4 : Ajouter un Bloc HTML dans Moodle
1. Connectez-vous en tant qu'administrateur
2. Sur n'importe quelle page (tableau de bord, cours), activez **Mode d'édition**
3. Cliquez sur **Ajouter un bloc** → choisissez **HTML**
4. Cliquez sur l'icône ✏️ du bloc pour l'éditer
5. Dans l'éditeur, cliquez sur **< >** (Source HTML)
6. Copiez-collez le code suivant :

```html
<script>
  window.HEP_API_KEY = 'sk-ant-VOTRE_CLE_ICI';
</script>
<div id="hep-chatbot-container"></div>
<script>
  const iframe = document.createElement('iframe');
  iframe.src = 'URL_DE_VOTRE_FICHIER_hep-chatbot.html';
  iframe.style.cssText = 'position:fixed;bottom:0;right:0;width:1px;height:1px;border:none;z-index:9999;';
  iframe.id = 'hep-bot-frame';
  document.body.appendChild(iframe);
</script>
```

> **Mieux :** Copiez tout le contenu du `<script>` et du `<style>` directement dans un bloc HTML Moodle.

#### Étape 5 : Méthode directe (Embed complet)
1. Dans le bloc HTML, collez TOUT le contenu entre `<body>` et `</body>` du fichier `hep-chatbot.html`
2. Supprimez les balises `<body>` et `</body>` elles-mêmes
3. Enregistrez

---

### MÉTHODE 2 – Plugin Local Moodle

#### Étape 1 : Créer la structure du plugin
```
moodle/local/hepchatbot/
├── version.php
├── lib.php
└── chatbot.html   (votre fichier hep-chatbot.html renommé)
```

#### Étape 2 : Fichier version.php
```php
<?php
defined('MOODLE_INTERNAL') || die();
$plugin->version   = 2024010100;
$plugin->requires  = 2020061500; // Moodle 3.9
$plugin->component = 'local_hepchatbot';
$plugin->fullname  = 'HEP Academy Chatbot';
```

#### Étape 3 : Fichier lib.php
```php
<?php
defined('MOODLE_INTERNAL') || die();

function local_hepchatbot_before_footer() {
    global $PAGE, $CFG;
    $apikey = get_config('local_hepchatbot', 'apikey');
    // Inject chatbot into every page footer
    $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            var script = document.createElement('script');
            script.innerHTML = 'window.HEP_API_KEY=\"" . $apikey . "\";';
            document.head.appendChild(script);
        });
    ");
    return true;
}
```

#### Étape 4 : Installer le plugin
1. Zippez le dossier `hepchatbot`
2. Admin Moodle → **Plugins** → **Installer un plugin**
3. Uploadez le ZIP
4. Suivez l'assistant d'installation
5. Configurez la clé API dans : Admin → **Plugins** → **Local** → **HEP Chatbot** → **Paramètres**

---

### MÉTHODE 3 – Injection via le thème Moodle

#### Modifier footer.mustache (Avancé)
1. Copiez votre thème actuel : `theme/votreTheme/templates/`
2. Ouvrez `footer.mustache`
3. Avant `</body>`, ajoutez :

```html
{{#chatbot_enabled}}
<script>window.HEP_API_KEY='{{chatbot_apikey}}';</script>
<!-- Collez ici tout le contenu du chatbot -->
{{/chatbot_enabled}}
```

---

### SÉCURITÉ IMPORTANTE ⚠️

**Ne jamais exposer votre clé API côté client en production !**

Solution recommandée : Créer un proxy PHP sur votre serveur Moodle :

```php
<?php
// /moodle/local/hepchatbot/api_proxy.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://votre-moodle.com');

$data = json_decode(file_get_contents('php://input'), true);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: ' . getenv('HEP_ANTHROPIC_KEY'), // Variable d'environnement serveur
        'anthropic-version: 2023-06-01'
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

echo curl_exec($ch);
curl_close($ch);
```

Puis dans le HTML du chatbot, remplacez l'URL de l'API :
```javascript
const response = await fetch('/local/hepchatbot/api_proxy.php', { ... });
```

---

## 🇬🇧 MOODLE INTEGRATION GUIDE (ENGLISH)

### Prerequisites
- Moodle administrator access  
- Anthropic API key (https://console.anthropic.com)
- Moodle 3.9 or higher

---

### QUICK METHOD – HTML Block

1. **Get API Key**: Visit https://console.anthropic.com → API Keys → Create Key
2. **Edit chatbot file**: Replace `YOUR_ANTHROPIC_API_KEY` with your actual key
3. **In Moodle**: Turn on Edit Mode → Add Block → HTML
4. **Paste the chatbot code** (everything inside `<body>`) into the HTML block source
5. **Save** and turn off Edit Mode

### RECOMMENDED: Proxy Setup for Security
Store your API key server-side using an environment variable:
```bash
# In your server environment / .env file
HEP_ANTHROPIC_KEY=sk-ant-your-key-here
```
Then use the PHP proxy (see French section above) to avoid exposing the key client-side.

---

## ✅ CHECKLIST DE DÉPLOIEMENT

- [ ] Clé API Anthropic obtenue et configurée
- [ ] Fichier HTML testé en local (ouvrir dans navigateur)
- [ ] Proxy PHP créé pour sécuriser la clé API
- [ ] Chatbot intégré dans Moodle (bloc HTML ou plugin)
- [ ] Test sur un cours pilote
- [ ] Vérification mobile (responsive)
- [ ] Communication aux étudiants

---

## 🎨 PERSONNALISATION

Pour personnaliser le chatbot :
- **Couleurs** : Modifiez les variables CSS dans `:root { --hep-blue, --hep-orange }`
- **Langue par défaut** : Changez `let lang = 'fr';` → `'en'`
- **Messages d'accueil** : Modifiez l'objet `i18n` dans le JS
- **Réponses IA** : Modifiez le `system` prompt dans `i18n[lang].system`
- **Suggestions rapides** : Modifiez le tableau `chips`

---

## 📞 SUPPORT

Pour toute question : contacter l'équipe technique HEP Academy
