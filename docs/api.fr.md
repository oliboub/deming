# API

Deming peut être modifié ou mis à jour via une REST API.

Une API REST ([Representational State Transfer](https://fr.wikipedia.org/wiki/Representational_state_transfer))
est une interface de programmation d'application qui respecte les contraintes du style d'architecture REST
et permet d'interagir avec les services web RESTful.

## Installer l'API

Pour installer l'API, il est nécessaire d'installer Passport en lançant cette commande :

```bash
php artisan passport:install
```

L'environnement Docker prend en charge cette fonctionnalité nativement, via l'[entrypoint](https://github.com/dbarzin/deming/blob/main/docker/entrypoint.sh).

## Les APIs

| Endpoint | Rôle |
|---|---|
| `/api/attributes` | Taxonomies d'attributs |
| `/api/domains` | Domaines / référentiels |
| `/api/controls` | **Mesures de sécurité** (exigences à mettre en œuvre, liées à un domaine) |
| `/api/measures` | **Instances d'audit** (vérifications périodiques des mesures de sécurité) |
| `/api/users` | Utilisateurs |
| `/api/documents` | Documents attachés aux instances d'audit |
| `/api/logs` | Journaux d'audit |

> **Rappel des rôles :** `controls` = mesures de sécurité (`domain_id`, `clause`, `objective`…).  
> `measures` = instances d'audit (`plan_date`, `realisation_date`, `status`, `score`…).

## Actions gérées par le contrôleur de ressources

Les requêtes et URI de chaque API sont représentées dans le tableau ci-dessous.

| Requête   | URI                 | Action                              |
|-----------|---------------------|-------------------------------------|
| GET       | /api/objets         | Renvoie la liste des objets         |
| GET       | /api/objets/{id}    | Renvoie l'objet {id}                |
| POST      | /api/objets         | Crée un nouvel objet                |
| PUT/PATCH | /api/objets/{id}    | Met à jour l'objet {id}             |
| DELETE    | /api/objets/{id}    | Supprime l'objet {id}               |

## Droits d'accès

Il faut s'identifier avec un utilisateur de l'application Deming pour pouvoir accéder aux API.
Cet utilisateur doit disposer du rôle "API".

Lorsque l'authentification réussit, l'API envoie un "token" qui doit être passé dans l'entête "Authorization" de la requête de l'API.

## Structure des réponses pour les endpoints show

### GET /api/controls/{id} — mesure de sécurité

Retourne les champs de la mesure de sécurité ainsi qu'une clé `controls` contenant les IDs
des instances d'audit qui vérifient cette mesure :

```json
{
  "id": 1,
  "domain_id": 1,
  "clause": "NIS2-Art.21.2.a",
  "name": "Analyse de Risques",
  "objective": "...",
  "controls": [1, 2, 3]
}
```

### GET /api/measures/{id} — instance d'audit

Retourne les champs de l'instance d'audit ainsi qu'une clé `measures` contenant les IDs
des mesures de sécurité couvertes par cette instance :

```json
{
  "id": 1,
  "name": "Revue formelle de l'analyse de risques",
  "plan_date": "2026-07-31",
  "realisation_date": null,
  "status": 0,
  "periodicity": 12,
  "measures": [1]
}
```

### POST/PUT /api/measures — champs liés optionnels

Lors de la création ou de la mise à jour d'une instance d'audit, les clés suivantes sont
acceptées pour mettre à jour les enregistrements liés :

| Clé | Effet |
|---|---|
| `measures` | Synchronise la liste des IDs de mesures de sécurité liées à cette instance |
| `actions` | Assigne des IDs d'actions à cette instance d'audit |
| `documents` | Assigne des IDs de documents à cette instance d'audit |
| `users` | Synchronise la liste des IDs d'utilisateurs assignés |
| `groups` | Synchronise la liste des IDs de groupes d'utilisateurs assignés |

## Exemples

Voici quelques exemples d'utilisation de l'API avec PHP :

### Authentification

```php
<?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://127.0.0.1:8000/api/login",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => http_build_query(
            array("email" => "api@admin.com",
                  "password" => "12345678")),
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "content-type: application/x-www-form-urlencoded",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $info = curl_getinfo($curl);

    curl_close($curl);

    if ($err) {
        set_error_handler($err);
    } else {
        if ($info['http_code'] == 200) {
            $token = json_decode($response)->token;
        } else {
            error_log($response);
            error_log("No login api status 403");
        }
    }
```

### Liste des domaines

```php
<?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://127.0.0.1:8000/api/domains",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => null,
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "Authorization: " . "Bearer" . " " . $token . "",
            "cache-control: no-cache",
            "content-type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    var_dump($response);
```

### Récupérer une mesure de sécurité

```php
<?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://127.0.0.1:8000/api/controls/1",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => null,
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "Authorization: " . "Bearer" . " " . $token . "",
            "cache-control: no-cache",
            "content-type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    var_dump($response);
```

### Mettre à jour un domaine

```php
<?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://127.0.0.1:8000/api/domains/8",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_POST => true,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => http_build_query(
            array(
                'title' => 'Nouveau titre',
                'description' => 'Nouvelle description',
            )
        ),
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "Authorization: " . "Bearer" . " " . $token . "",
            "cache-control: no-cache",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    var_dump($response);
```

### Récupérer une instance d'audit

```php
<?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://127.0.0.1:8000/api/measures/1",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => null,
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "Authorization: " . "Bearer" . " " . $token . "",
            "cache-control: no-cache",
            "content-type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    var_dump($response);
```

## Python

Voici un exemple d'utilisation de l'API en Python :

```python
#!/usr/bin/python3

import requests

vheaders = {}
vheaders['accept'] = 'application/json'

print("Login")
response = requests.post("http://127.0.0.1:8000/api/login",
    headers=vheaders,
    data= {'email':'api@admin.localhost', 'password':'12345678'} )
print(response.status_code)

vheaders['Authorization'] = "Bearer " + response.json()['token']

print("Récupérer les mesures de sécurité")
response = requests.get("http://127.0.0.1:8000/api/controls", headers=vheaders)
print(response.status_code)
print(response.json())

print("Récupérer les instances d'audit")
response = requests.get("http://127.0.0.1:8000/api/measures", headers=vheaders)
print(response.status_code)
print(response.json())
```

## bash

Voici un exemple d'utilisation de l'API en ligne de commande avec [CURL](https://curl.se/docs/manpage.html) et [JQ](https://stedolan.github.io/jq/) :

```bash
# identifiants valides
data='{"email":"api@admin.localhost","password":"12345678"}'

# obtenir un token après connexion réussie
token=$(curl -s -d ${data} -H "Content-Type: application/json" http://localhost:8000/api/login | jq -r .token)

# lister les mesures de sécurité
curl -s -H "Content-Type: application/json" -H "Authorization: Bearer ${token}" "http://127.0.0.1:8000/api/controls" | jq .

# lister les instances d'audit
curl -s -H "Content-Type: application/json" -H "Authorization: Bearer ${token}" "http://127.0.0.1:8000/api/measures" | jq .
```
