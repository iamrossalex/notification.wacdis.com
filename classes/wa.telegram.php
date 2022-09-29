<?php
	namespace WA\Bots;
	
	class Telegram {
		private $token;
		function __construct($token) {
			$this->token = $token;
		}
		private function registerWebHook() {}
		private function send() {}
		private function sendRequest() {}

		public function request($method, $params = array(), $options = array()) {
			$options += array(
				'http_method' => 'POST',
				'timeout' => 10,
				'connect_timeout' => 10
			);
			$netDelay = 1;
			$params_arr = array();
			foreach ($params as $key => &$val) {
				if (!is_numeric($val) && !is_string($val))
					$params_arr[] = urlencode($key) . '=' . json_encode($val);
				else
					$params_arr[] = urlencode($key) . '=' . urlencode($val);
			}
			$query_string = implode('&', $params_arr);
			$url = 'https://api.telegram.org/bot'.$this->token.'/'.$method;
			$curl = curl_init();
			$curl_opts = [
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $query_string,
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => $options['connect_timeout'],
				CURLOPT_TIMEOUT => $options['timeout']
			];
			curl_setopt_array($curl,$curl_opts);
			$response_str = curl_exec($curl);
			$errno = curl_errno($curl);
			$http_code = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
			if ($http_code == 401) {
				throw new Exception('Invalid access token provided');
			} else if ($http_code >= 500 || $errno) {
				sleep($netDelay);
				if ($netDelay < 30) {
					$netDelay *= 2;
				}
			}
			// $response_str = json_decode($response_str, true);
			return $response_str;
		}
		public function getFileLink($file_id) {
            $res = $this->request("getFile", ["file_id" => $file_id]);
            if ($res['ok'] == 1) $res = 'https://api.telegram.org/file/bot681202882:AAHG7pVN5H2AMRtYQcWMlFs20rCrtyOe_v8/' . $res['result']['file_path'];
			return $res;
		}
	}
?>
