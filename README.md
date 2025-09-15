# Tripsure Quote API

This is a simple REST API for storing customer and vehicle details and generating quotes

### Dependencies

* PHP 8
* MySQL
* Postman API Client
* MySQL Client to inspect data (e.g. Sequel Ace)

### Installing

* Import Postman collection 'Tripsure-Quote-Collection.json' and environment 'Tripsure.postman_environment.json' to Postman client
* Start MySQL locally and add the local database connection details to lines 10-13 in api.php
* Import schema.sql into your local database and ensure tables are present
* Start up the local PHP server routing all requests through 'api.php'

```
php -S localhost:8000 api.php
```

### Executing program

* Using Postman, create customers using the 'Create Customer' POST action
* Add vehicles using the 'Add Vehicle' POST action being sure to enter valid customer IDs
  (these are provided in the response to the 'Create Customer Action')
* Use the 'Get Quote' action to generate quotes being sure to pass through correct customer and vehicle IDs. These are
  calculated and stored in the quote table.

### Author

Jonathan Hamler
(https://github.com/jkhamler)
