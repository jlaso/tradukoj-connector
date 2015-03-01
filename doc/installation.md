# Installation

The recommended way to install **Tradukoj connector** is through [Composer](http://packagist.org/about-composer). 

Download the composer binary:

``` bash
wget http://getcomposer.org/composer.phar
# or
curl -O http://getcomposer.org/composer.phar
```

Now, install **Tradukoj connector** requiring it into composer.json:

``` json
{
   ...
    "require": {
        ...
        "jlaso/tradukoj-connector": "dev-master"
        ...
    }
}
```

And now you can launch ```composer update``` in order to include the connector in your development.

Once added the autoloader you will have access to the library:

``` php
<?php

require 'vendor/autoload.php';
```

[![Latest Stable Version](https://poser.pugx.org/jlaso/tradukoj-connector/v/stable.png)](https://packagist.org/packages/jlaso/tradukoj-connector)
[![Latest Unstable Version](https://poser.pugx.org/jlaso/tradukoj-connector/v/unstable.png)](https://packagist.org/packages/jlaso/tradukoj-connector)

***

Next section: [Extending **Tradukoj connector**](https://github.com/jlaso/tradukoj-connector/blob/master/doc/usage.md).

