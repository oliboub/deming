# API

Deming can be modified or updated via a REST API.

A REST API ([Representational State Transfer](https://fr.wikipedia.org/wiki/Representational_state_transfer))
is an application programming interface that respects the constraints of the REST
architecture and enables interaction with RESTful web services.

## Installing the API

To install the API, you need to install Passport by running this command:

```bash
php artisan passport:install
```

The Docker environment supports this functionality natively, via the [entrypoint](https://github.com/dbarzin/deming/blob/main/docker/entrypoint.sh).

## The APIs

| Endpoint | Role |
|---|---|
| `/api/attributes` | Attribute taxonomies |
| `/api/domains` | Domains / frameworks |
| `/api/controls` | **Security measures** (requirements to implement, linked to a domain) |
| `/api/measures` | **Audit instances** (periodic verifications of security measures) |
| `/api/users` | Users |
| `/api/documents` | Documents attached to audit instances |
| `/api/logs` | Audit logs |

> **Role reminder:** `controls` = security measures (`domain_id`, `clause`, `objective`…).  
> `measures` = audit instances (`plan_date`, `realisation_date`, `status`, `score`…).

## Actions managed by the resource controller

Requests and URIs for each API are shown in the table below.

| Request   | URI                 | Action                        |
|-----------|---------------------|-------------------------------|
| GET       | /api/objects        | Returns the list of objects   |
| GET       | /api/objects/{id}   | Returns object {id}           |
| POST      | /api/objects        | Creates a new object          |
| PUT/PATCH | /api/objects/{id}   | Updates object {id}           |
| DELETE    | /api/objects/{id}   | Deletes object {id}           |

## Access rights

To access the APIs, you need to identify yourself as a Deming application user.
This user must have the "API" role.

When authentication is successful, the API sends a "token" which must be passed in the "Authorization" header of the API request.

## Response structure for show endpoints

### GET /api/controls/{id} — security measure

Returns the security measure fields plus a `controls` key containing the IDs of the
audit instances that verify this measure:

```json
{
  "id": 1,
  "domain_id": 1,
  "clause": "NIS2-Art.21.2.a",
  "name": "Risk Analysis",
  "objective": "...",
  "controls": [1, 2, 3]
}
```

### GET /api/measures/{id} — audit instance

Returns the audit instance fields plus a `measures` key containing the IDs of the
security measures covered by this audit instance:

```json
{
  "id": 1,
  "name": "Formal review of the risk analysis",
  "plan_date": "2026-07-31",
  "realisation_date": null,
  "status": 0,
  "periodicity": 12,
  "measures": [1]
}
```

### POST/PUT /api/measures — optional related fields

When creating or updating an audit instance, the following keys are accepted to
update related records:

| Key | Effect |
|---|---|
| `measures` | Sync the list of security measure IDs linked to this audit instance |
| `actions` | Assign action IDs to this audit instance |
| `documents` | Assign document IDs to this audit instance |
| `users` | Sync the list of assigned user IDs |
| `groups` | Sync the list of assigned user group IDs |

## Examples

Here are a few examples of how to use the API with PHP:

### Authentication

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

### List domains

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

### Get a security measure

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

### Update a security measure

```php
<?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://127.0.0.1:8000/api/controls/1",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_POST => true,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => http_build_query(
            array(
                'name' => 'Updated name',
                'objective' => 'Updated objective',
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

### Get an audit instance

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

Here is an example of using the API in Python:

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

print("Get security measures")
response = requests.get("http://127.0.0.1:8000/api/controls", headers=vheaders)
print(response.status_code)
print(response.json())

print("Get audit instances")
response = requests.get("http://127.0.0.1:8000/api/measures", headers=vheaders)
print(response.status_code)
print(response.json())
```

## bash

Here is an example of using the API from the command line with [CURL](https://curl.se/docs/manpage.html) and [JQ](https://stedolan.github.io/jq/):

```bash
# valid login and password
data='{"email":"api@admin.localhost","password":"12345678"}'

# get a token after correct login
token=$(curl -s -d ${data} -H "Content-Type: application/json" http://localhost:8000/api/login | jq -r .token)

# list security measures
curl -s -H "Content-Type: application/json" -H "Authorization: Bearer ${token}" "http://127.0.0.1:8000/api/controls" | jq .

# list audit instances
curl -s -H "Content-Type: application/json" -H "Authorization: Bearer ${token}" "http://127.0.0.1:8000/api/measures" | jq .
```
