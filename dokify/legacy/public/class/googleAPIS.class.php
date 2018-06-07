<?php
	class googleAPIS {
		const API_KEY = "AIzaSyCXk-m2W1u-796B9lFjVWvqbNmlqnNUCZ0";

		public static function expand($url){
			$response = self::URLShortener($url, true);
		    return isset($response['longUrl']) ? $response['longUrl'] : false;
		}

		public static function short($url){
			$response = self::URLShortener($url);
			$n = 0;
			$sleepTime = 0;
			while (($sleepTime < 128) && (isset($response['error']) && $response['error']['message'] == 'Rate Limit Exceeded')) {
				$n++;
				$sleepTime = pow(2, $n);				
				sleep($sleepTime);
				$response = self::URLShortener($url);
			}
			return isset($response['id']) ? $response['id'] : false;
		}

		protected static function URLShortener($URL, $shorten = true){
			// Creditos a: http://davidwalsh.name/google-url
			$APIURL = 'https://www.googleapis.com/urlshortener/v1/url?key=' . googleAPIS::API_KEY;

			// Create cURL
			$ch = curl_init();
			// If we're shortening a URL...
			if($shorten) {
				curl_setopt($ch, CURLOPT_URL, $APIURL);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("longUrl"=> $URL)));
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
			} else {
				curl_setopt($ch, CURLOPT_URL, $APIURL.'&shortUrl='. $URL);
			}
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			// Execute the post
			$result = curl_exec($ch);
			// Close the connection
			curl_close($ch);
			// Return the result
			return json_decode($result, true);
		}

	}
?>
