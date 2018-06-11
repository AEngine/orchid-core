Orchid-Core
====
Orchid is lightweight web application framework.
This library attempts to comply with PSR-1, PSR-2, PSR-4 and PSR-11. 

Some parts of the project were influenced by: Laravel, Slim and Symfony Framework's. Thank you!

#### Requirements
* PHP >= 7.0

#### Installation
Run the following command in the root directory of your web project:
> `composer require aengine/orchid`

#### Usage
Create an `index.php` file with the following contents:

```php
<?php

require_once 'vendor/autoload.php';

$app = AEngine\Orchid\App::getInstance();

$app->router()->get('/hello/:name', function ($request, $response, $args) {
    return $response->write("Hello, " . $args['name']);
});

$app->run();
```

Open your browser on page: `http://[hostname]/hello/World`

#### Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

#### License
The Orchid Framework is licensed under the MIT license. See [License File](LICENSE.md) for more information.

#### Extensions

##### Memory
Work with Key-Value storage
> `composer require aengine/orchid-memory`

##### Database
Attach the database in the project by using a wrapper around the PDO
> `composer require aengine/orchid-database`

##### Misc
Functional add-ons
> `composer require aengine/orchid-misc`

##### Filter
Validate incoming data
> `composer require aengine/orchid-filter`
