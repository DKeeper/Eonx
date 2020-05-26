# EonX Test Task

This project is the code base of the PHP Test Task for the company [EonX][1]. 
It is based on a really simple version of [Laravel Lumen][2].

## Context
This test task requires you to implement a new feature into an existing RESTful API.

The API is built to interact with [MailChimp via their API][3], handling CRUD operations for [LISTS][4] and [MEMBERS][5].

This task assumes all interaction will take place via this API, therefore data should be stored locally and 
only retrieved from MailChimp when required. 

The current API contains code which allows the creation, retrieval, update and deletion of lists. 
You are required to add a feature to this existing code to allow the creation, retrieval update, and 
deletion of members within a list.

## Scope
The implementation for [LISTS][4] have been made already. The scope of this task is to update the current code base to
implement CRUD operations for members:

- Add members to a list
- Update members within a list
- Remove members from a list
- Retrieve members from a list

## Requirements
This task requirements are as follows:

- Each external libraries are loaded via [composer][9]
- The database layer used is [Doctrine][6] via the [laravel-doctrine/orm][7] package
- The interaction with [MailChimp API][3] is made using [pacely/mailchimp-apiv3][8]

## Get Started
- [Register on MailChimp][10], create your API key to use in your application

To complete this task you can either:
- Fork this repository, update the code base and send the URL of your repository to the reviewer(s)
- Clone this repository into your local environment, update the code base and send a zip of the repository to the reviewer(s)

# Added by Dmitry Kapenkin

## Make tables into DB

`php artisan doctrine:schema:update`

## Endpoints of API for [Members][5]

- show all members of List/Audience

`/mailchimp/lists/{listId}/members`
```
curl --location --request GET 'HOST/mailchimp/lists/5baa195e64/members' --data-raw ''
```
Response
```
[
    {
        "member_id": "068114b6-9eb6-11ea-861f-00ffc9c08a2e",
        "list_id": "5baa195e64",
        "email_address": "test3@ya.ru",
        "status": "cleaned",
        "merge_fields": {
            "FNAME": "John",
            "LNAME": "Doe",
            "PHONE": "+79890865595",
            "ADDRESS": {
                "zip": "141452",
                "city": "Moscow",
                "addr1": "My street",
                "addr2": "My street",
                "state": "Moscow reg.",
                "country": "RU"
            },
            "BIRTHDAY": "10/17"
        },
        "language": "ru",
        "vip": true
    },
    {
        "member_id": "b6bea893-9eb5-11ea-861f-00ffc9c08a2e",
        "list_id": "5baa195e64",
        "email_address": "test2@ya.ru",
        "status": "cleaned",
        "merge_fields": {
            "FNAME": "John",
            "LNAME": "Doe",
            "PHONE": "+79890865595",
            "ADDRESS": {
                "zip": "141452",
                "city": "Moscow",
                "addr1": "My street",
                "addr2": "My street",
                "state": "Moscow reg.",
                "country": "RU"
            },
            "BIRTHDAY": "03/25"
        },
        "language": "ru",
        "vip": true
    },
    {
        "member_id": "dbb41a66-9eb6-11ea-861f-00ffc9c08a2e",
        "list_id": "5baa195e64",
        "email_address": "test4@ya.ru",
        "status": "cleaned",
        "merge_fields": {
            "FNAME": "John",
            "LNAME": "Doe",
            "PHONE": "+79890865595",
            "ADDRESS": {
                "zip": "141452",
                "city": "Moscow",
                "addr1": "My street",
                "addr2": "My street 2",
                "state": "Moscow reg.",
                "country": "RU"
            },
            "BIRTHDAY": "12/01"
        },
        "language": "ru",
        "vip": true
    }
]
```
- show a current member of List/Audience

`/mailchimp/lists/{listId}/members/{email}`
```
curl --location --request GET 'HOST/mailchimp/lists/5baa195e64/members/test4@ya.ru' --data-raw ''
```
Response
```
{
    "member_id": "dbb41a66-9eb6-11ea-861f-00ffc9c08a2e",
    "list_id": "5baa195e64",
    "email_address": "test4@ya.ru",
    "status": "cleaned",
    "merge_fields": {
        "FNAME": "John",
        "LNAME": "Doe",
        "PHONE": "+79890865595",
        "ADDRESS": {
            "zip": "141452",
            "city": "Moscow",
            "addr1": "My street",
            "addr2": "My street 2",
            "state": "Moscow reg.",
            "country": "RU"
        },
        "BIRTHDAY": "12/01"
    },
    "language": "ru",
    "vip": true
}
```
- create a member in List/Audience

`/mailchimp/lists/{listId}/members`
```
curl --location --request POST 'HOST/mailchimp/lists/5baa195e64/members' --header 'Content-Type: application/json' --data-raw '{
	"email_address": "test99@ya.ru",
	"status": "cleaned",
	"merge_fields": {
		"FNAME": "John",
		"LNAME": "Doe",
		"PHONE": "+79890865595",
		"ADDRESS": {
			"zip": "141452",
			"city": "Moscow",
			"addr1": "My street",
			"addr2": "My street 2",
			"state": "Moscow reg.",
			"country": "RU"
		},
		"BIRTHDAY": "12/01"
 	},
 	"language": "zz",
 	"vip": true
}'
```
Response
```
{
    "member_id": "2121312c-9f51-11ea-861f-00ffc9c08a2e",
    "list_id": "5baa195e64",
    "email_address": "test99@ya.ru",
    "status": "cleaned",
    "merge_fields": {
        "FNAME": "John",
        "LNAME": "Doe",
        "PHONE": "+79890865595",
        "ADDRESS": {
            "zip": "141452",
            "city": "Moscow",
            "addr1": "My street",
            "addr2": "My street 2",
            "state": "Moscow reg.",
            "country": "RU"
        },
        "BIRTHDAY": "12/01"
    },
    "language": "zz",
    "vip": true
}
```
- delete a member from List/Audience
`/mailchimp/lists/{listId}/members/{email}`
```
curl --location --request DELETE 'HOST/mailchimp/lists/5baa195e64/members/test99@ya.ru' --data-raw ''
```
Response
```
[]
```

- update a member from List/Audience
`/mailchimp/lists/{listId}/members/{email}`
```
curl --location --request PATCH 'http://eonx.loc/mailchimp/lists/5baa195e64/members/test99@ya.ru' --header 'Content-Type: application/json' --data-raw '{
	"email_address": "test99@ya.ru",
	"status": "cleaned",
	"merge_fields": {
		"FNAME": "John",
		"LNAME": "Doe",
		"PHONE": "+79890865595",
		"ADDRESS": {
			"zip": "141452",
			"city": "Moscow",
			"addr1": "My street",
			"addr2": "My street 2",
			"state": "Moscow reg.",
			"country": "RU"
		},
		"BIRTHDAY": "12/01"
 	},
 	"language": "ru",
 	"vip": true
}'
```
Response
```
{
    "member_id": "2121312c-9f51-11ea-861f-00ffc9c08a2e",
    "list_id": "5baa195e64",
    "email_address": "test99@ya.ru",
    "status": "cleaned",
    "merge_fields": {
        "FNAME": "John",
        "LNAME": "Doe",
        "PHONE": "+79890865595",
        "ADDRESS": {
            "zip": "141452",
            "city": "Moscow",
            "addr1": "My street",
            "addr2": "My street 2",
            "state": "Moscow reg.",
            "country": "RU"
        },
        "BIRTHDAY": "12/01"
    },
    "language": "ru",
    "vip": true
}
```

## Unit tests

Run `vendor/phpunit/phpunit/phpunit tests/Unit --coverage-html '/path/to/report/folder'`
Expected output
```
OK (21 tests, 70 assertions)

Generating code coverage report in HTML format ... done
```

[1]: https://eonx.com
[2]: https://lumen.laravel.com
[3]: http://developer.mailchimp.com/documentation/mailchimp/reference/overview
[4]: http://developer.mailchimp.com/documentation/mailchimp/reference/lists
[5]: http://developer.mailchimp.com/documentation/mailchimp/reference/lists/members
[6]: http://www.doctrine-project.org/projects/orm.html
[7]: https://www.laraveldoctrine.org/docs/1.3/orm
[8]: https://github.com/pacely/mailchimp-api-v3
[9]: https://getcomposer.org/
[10]: https://login.mailchimp.com/signup/
