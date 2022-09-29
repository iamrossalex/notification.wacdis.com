<?php
	function gotoUrl($url,$time = '0',$meta = false) {
			if ($meta) echo "<meta http-equiv='refresh' content='".$time.";URL=".$url."'>"; else {
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: ".$url);
			}
			exit;
		}
	function file_force_download($file,$filename = false,$filetype = false) {
			if (file_exists($file)) {
				if (ob_get_level()) ob_end_clean();
				header('Content-Description: File Transfer');
				if ($filetype) header('Content-Type: ' . $filetype); else header('Content-Type: ' . mime_content_type($file));
				if ($filename) header('Content-Disposition: attachment; filename=' . basename($filename)); else header('Content-Disposition: attachment; filename=' . basename($file));
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));
				if ($fd = fopen($file, 'rb')) {
					while (!feof($fd)) print fread($fd, 1024);
					fclose($fd);
				}
			}
		}
	function fileToContent($filename) {
			$file_size = filesize($filename);
			$handle = fopen($filename, "r");
			$content = fread($handle, $file_size);
			fclose($handle);
			return chunk_split(base64_encode($content));
		}
	function mailto($to, $subject, $body, $auth) {
			// print_r($auth);return true;
			$mail 				= new PHPMailer();
			$mail->IsSMTP();
			$mail->Host 		= $auth['host'];
			$mail->CharSet		= 'UTF-8';
			$mail->SMTPAuth 	= true;
			$mail->SMTPSecure 	= $auth['secure'];
			$mail->Port 		= $auth['port'];
			$mail->Username 	= $auth['email'];
			$mail->Password 	= $auth['password'];
			$mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				)
			);
			$mail->SMTPDebug	= 0;
			$mail->Subject		= $subject;
			$mail->SetFrom($auth['email'], $auth['name']);
			$mail->addReplyTo($auth['email'], $auth['name']);
			if (is_array($to)) $mail->AddAddress($to[1], $to[0]); else $mail->AddAddress($to);
			$mail->MsgHTML($body);
			if(!$mail->Send()) return false; else return true;
		}
	function passgen($chars = 8, $isHard = false) {
			if (!is_numeric($chars) || $chars < 1) $chars = 8;
			if ($isHard) $alphabet = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ123456789!@#$%&*()-_=+{}[];:<>?';
				else $alphabet = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ123456789';
			$pass = array(); //remember to declare $pass as an array
			$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
			for ($i = 0; $i < $chars; $i++) {
				$n = rand(0, $alphaLength);
				$pass[] = $alphabet[$n];
			}
			return implode($pass);
		}
	function sendRequest($method, $uri, $json = NULL, $options = NULL) {
			$adb_option_defaults = array(
				CURLOPT_HEADER => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 2,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_VERBOSE => true,
				CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)',
			);
			$adb_handle = curl_init();
			$options = array(
				CURLOPT_URL => $uri,
				CURLOPT_CUSTOMREQUEST => $method, // GET POST PUT PATCH DELETE HEAD OPTIONS
				CURLOPT_POSTFIELDS => $json,
			);
			curl_setopt_array($adb_handle,($options + $adb_option_defaults));
			return curl_exec($adb_handle);
		}
	function errorJson($code = -1,$text = 'Unknown error') {
			return json_encode('{
				"error" => $code,
				"error_message" => $text
			}');
		}
	function loadJson($file = "php://input") {
			if ($file != "php://input" && !file_exists($file)) return false;
			$request = file_get_contents($file);
			if (!empty($request)) {
				try {
					return json_decode($request, JSON_OBJECT_AS_ARRAY);
				} catch (Exception $e) {
					return false;
				}
			}
		}
	function is_array_assoc(array $arr) {
			if ([] === $arr) return false;
			return array_keys($arr) !== range(0, count($arr) - 1);
		}
	function arrayTofile(array $a, int $depth = 1) {
			if (!is_array($a)) return false;
			if ($depth > 1) $rslt = ""; else $rslt = "<?php\n\treturn ";
			$rslt .= "[\n";
			$tabs = ''; for ($c = 0; $c < $depth; $c++) $tabs .= "\t";
			$c = 0; $count = count($a) - 1;
			if (is_array_assoc($a)) {
				foreach ($a as $k => $v) {
					$rslt .= $tabs."\t";
					if (is_array($v)) $rslt .= ((is_numeric($k))?($k):('"'.$k.'"')).' => '.arrayTofile($v,$depth + 1); 
					else $rslt .= ((is_numeric($k))?($k):('"'.$k.'"')).' => '.((is_numeric($v))?($v):('"'.((strpos($v,'"') !== FALSE)?(str_replace(["\\","\""],["\\\\","\\\""],$v)):($v)).'"'));
					if ($c < $count) {$rslt .= ",\n";$c++;} else $rslt .= "\n";
				}
			} else {
				for ($c = 0; $c <= $count; $c++) {
					$rslt .= $tabs."\t";
					if (is_array($a[$c])) $rslt .= arrayTofile($a[$c],$depth + 1); 
					else $rslt .= ((is_numeric($a[$c]))?($a[$c]):('"'.((strpos($a[$c],'"') !== FALSE)?(str_replace(["\\","\""],["\\\\","\\\""],$a[$c])):($a[$c])).'"'));
					if ($c < $count) {$rslt .= ",\n";} else $rslt .= "\n";
				}
			}
			if ($depth > 1) $rslt .= $tabs.']'; else $rslt .= $tabs."];\n?>";

			return $rslt;
		}
	function storeJson($file,$array) {}
	function microtime_float() {
			list($usec, $sec) = explode(" ", microtime());
			return ((float)$usec + (float)$sec);
		}
	function q($sql) {global $mySql; $res = mysqli_query($mySql, $sql); if ($res) return $res; else {return false;}}
	function insert_id() {global $mySql;return mysqli_insert_id($mySql);}
	function mres($var) {global $mySql; if ($var) return mysqli_real_escape_string($mySql, $var);}
	function fr($res) {if ($res) mysqli_free_result($res);}
	function nr($res = null) {if ($res) {$ret = mysqli_fetch_array($res, MYSQLI_ASSOC);return $ret;} else return false;}
	function qa($sql) {
			$req = q($sql);
			$ret = [];
			while(($res = nr($req)) !== null) $ret[] = $res;
			return $ret;
		}
	function q1($sql,$key = false) {$res = q($sql);if (($ret = nr($res)) !== FALSE) {fr($res);if ($key) return $ret[($key)]; else return $ret;}return false;}
?>
