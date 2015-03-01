# Usage

This is only a simple example, the full implementation is more longer that this.

See [this file](https://github.com/jlaso/tradukoj-connector/blob/master/examples/simple.php)

```php
<?php

require_once __DIR__.'/../vendor/autoload.php';

use JLaso\TradukojConnector\Model\Loader\ArrayLoader;
use JLaso\TradukojConnector\ClientSocketApi;
use JLaso\TradukojConnector\Socket\Socket;
use JLaso\TradukojConnector\PostClient\PostCurl;
use JLaso\TradukojConnector\Output\ConsoleOutput;

$loader = new ArrayLoader();
$config = $loader->load(
    array(
        'project_id' => 1,
        'key' => 'key',
        'secret' => 'secret',
        'url' => 'https://localhost/api/'
    )
);

$socketClient = new Socket();
$postClient = new PostCurl();
$consoleOutput = new ConsoleOutput();

$clientSocketApi = new ClientSocketApi($config, $socketClient, $postClient, $consoleOutput, true);

// initialize client
$clientSocketApi->init();

// getters

// fetch the list of bundles of the project
$bundles = $clientSocketApi->getBundleIndex();

// get the list of catalogs of the project
$catalogs = $clientSocketApi->getCatalogIndex();
$keys = $clientSocketApi->getKeyIndex($bundles[0]);

$messages = $clientSocketApi->getMessages($bundles[0], $keys[0]);
```


***

Next section: [Installation](https://github.com/jlaso/tradukoj-connector/blob/master/doc/tests.md).

Previous section: [Examples](https://github.com/jlaso/tradukoj-connector/blob/master/doc/installation.md).
