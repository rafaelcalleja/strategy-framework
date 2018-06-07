<?php
/* Low level library to encode and decode messages using JSON
and sending those messages through HTTP with cURL */
abstract class QuadernoJSON {
  static function exec($url, $method, $username, $password, $data=null) {
    // Initialization
    $ch = curl_init($url);

    // Encode data in JSON
    $json = $data ? json_encode($data) : null;

    // cURL configuration options
    $options = array (
      CURLOPT_RETURNTRANSFER => true,                                 // Accept answer
      CURLOPT_USERPWD => $username . ":" . $password,                 // User and password
      CURLOPT_CUSTOMREQUEST => $method,                               // HTTP method to use
      CURLOPT_HTTPHEADER => array('Content-type: application/json'),  // JSON headers,
      CURLOPT_SSL_VERIFYPEER => false
      );

    if ($json) $options += array(CURLOPT_POSTFIELDS => $json);

    curl_setopt_array($ch, $options);

    // Get results
    $result['data'] = curl_exec($ch);
    $result['error'] = curl_errno($ch);
    $result['format_error'] = curl_error($ch);
    $result += curl_getinfo($ch);
    curl_close($ch);

    // Decode data
    if ($result['data']) $result['data'] = json_decode($result['data'], true);

    $app = \Dokify\Application::getInstance();
    $app['log.quaderno']->addDebug("http", [
      "url"    => $url,
      "method" => $method,
      "data"   => $data,
      "result" => $result,
    ]);

    return $result;
  }
}