<?php

namespace app\Core\Inner\Elastic;

use Elasticsearch\ClientBuilder;

class ElasticClient {

  private $client = null;

  function __construct() {
    $hosts = [

      [
        'host' => 'elastic.wozot.com',
        'port' => '9200',
        'scheme' => 'http',
        'user' => 'elastic',
        'pass' => 'm11gylkrhd'
      ],

      // you can add an array of hosts
      //[
      //  'host' => 'localhost',    // Only host is required
      //]
    ];
    $this->client = ClientBuilder::create()// Instantiate a new ClientBuilder
    ->setHosts($hosts)// Set the hosts
    ->build();              // Build the client object

  }


  function search($query = 'alphabet', $language = false, $num = 50, $pag = 0, $scroll = '10s') {

    //TODO: due query, 1=> main / 2=>related

    $pag = ($pag !== 0) ? ($num * $pag) : 0;

    $filter = [];
    if(!empty($language) && $language !== 'all') {
      $filter = [
        'term' => [
          'post_lang' => $language
        ]
      ];
    }

    $params = [
      'index' => 'items',
      'type' => 'item',
      'scroll' => $scroll,
      'from' => $pag, //pagina con n size alla volta.
      'size' => $num,
      'body' => [
        'query' => [
          'bool' => [
            'must' => [
              "query_string" => [
                "default_field" => "text",
                "query" => $query
              ],
            ],
            'filter' => $filter
          ],
        ]
      ]
    ];

    $results = [];
    $results['params'] = $params;
    $results = $this->client->search($params);
    return $results;
  }

  function scroll($scroll_id = null) {
    $response = $this->client->scroll([
        "scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
        "scroll" => "0s"            // and the same timeout window
      ]
    );
    return $response;
  }


}