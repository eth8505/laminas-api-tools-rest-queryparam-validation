# LaminasRestQueryParamValidation - Laminas Module for api-tools-rest QueryString validation
The **LaminasRestQueryParamValidation** module allows you to validate query parameters with
[laminas-api-tools/api-tools-rest](https://github.com/laminas-api-tools/api-tools-rest) just like you would with 
[laminas-api-tools/api-tools-content-validation](https://github.com/laminas-api-tools/api-tools-content-validation) for entities.

[![CI Status](https://github.com/eth8505/laminas-api-tools-rest-queryparam-validation/workflows/phpunit/badge.svg)](https://github.com/eth8505/laminas-api-tools-rest-queryparam-validation/actions)
![Packagist](https://img.shields.io/packagist/dt/eth8505/laminas-api-tools-rest-queryparam-validation.svg)
![Packagist Version](https://img.shields.io/packagist/v/eth8505/laminas-api-tools-rest-queryparam-validation.svg)
![PHP from Packagist](https://img.shields.io/packagist/php-v/eth8505/laminas-api-tools-rest-queryparam-validation.svg)

## How to install

Install `eth8505/laminas-api-tools-rest-queryparam-validation` package via composer.

~~~bash
$ composer require eth8505/laminas-api-tools-rest-queryparam-validation
~~~

Load the module in your `application.config.php` file like so:

~~~php
<?php

return [
	'modules' => [
		'LaminasRestQueryParamValidation',
		// ...
	],
];
~~~

## How to use

Just like with [laminas-api-tools/api-tools-content-validation](https://github.com/laminas-api-tools/api-tools-content-validation), specify a
`query_filter` key in the `api-tools-content-validation` section of your `module.config.php` and register a
`input_filter_spec`. The [Laminas API Tools docs](https://api-tools.getlaminas.org/documentation/content-validation/advanced)
dig into this a little deeper.

### Generic query param validation for a rest controller
~~~php
<?php
return [
// ...
    'api-tools-content-validation' => [
        'MyModule\\V1\\Rest\\MyModule\\Controller' => [
            'query_filter' => 'MyModule\\V1\\Rest\\MyModule\\QueryValidator',
        ],
    ],
// ...
    'input_filter_specs' => [
        'MyModule\\V1\\Rest\\MyModule\\QueryValidator' => [
            0 => [
                'required' => false,
                'validators' => [
                    // ...
                ],
                'filters' => [],
                'name' => 'my_param',
                'field_type' => 'integer',
            ],
        ],
    ],
];
~~~

### Action-specific query-validation
~~~php
<?php
return [
// ...
    'api-tools-content-validation' => [
        'MyModule\\V1\\Rest\\MyModule\\Controller' => [
            'query_filter' => [
                'default' => 'MyModule\\V1\\Rest\\MyModule\\QueryValidator',
                'fetchAll' => 'MyModule\\V1\\Rest\\MyModule\\FetchAllQueryValidator'
            ],
        ],
    ],
// ...
    'input_filter_specs' => [
        'MyModule\\V1\\Rest\\MyModule\\QueryValidator' => [
            0 => [
                'required' => false,
                'validators' => [
                    // ...
                ],
                'filters' => [],
                'name' => 'my_param',
                'field_type' => 'integer',
            ],
        ],
        'MyModule\\V1\\Rest\\MyModule\\FetchAllQueryValidator' => [
            0 => [
                'required' => false,
                'validators' => [
                    // ...
                ],
                'filters' => [],
                'name' => 'my_fetch_all_param',
                'field_type' => 'integer',
            ], 
        ]
    ],
];
~~~
 
## Thanks
Thanks to [jdelisle](https://github.com/jdelisle) and his 
[Query String validation gist](https://gist.github.com/jdelisle/e10dfab05427e553a7d0#file-queryvalidationlistener-php-L120)
which this module is based on.
