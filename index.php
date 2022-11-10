<?php
	ini_set("display_errors", "0");
	error_reporting(0);

	define("DEBUG", true);

	header("Access-Control-Allow-Origin: *"); // Должен возвращать или *, или header('Origin')
	header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

	require("classes/functions.php");
	require("classes/wa.telegram.php");
	use WA\Bots;
	$tg = new WA\Bots\Telegram('botkey');

	$mySql = mysqli_connect('127.0.0.1', 'U','P', 'DB');
	q("SET NAMES utf8");

	switch ($_SERVER['REQUEST_URI']) {
		case "/@telegram":
			require_once("telegram.php");
			break;
		case "/api/v1/send":
			$m = file_get_contents('php://input');
			if (DEBUG) file_put_contents('json_logs/__req.txt', ($m.PHP_EOL), FILE_APPEND);
			$m = json_decode($m,true);
			if (empty($m['sid'])) die(json_encode([
				"error" => 1,
				"message" => "Source ID is not defined"
			]));
			if (empty($m['message'])) die(json_encode([
				"error" => 2,
				"message" => "Message is empty"
			]));
			$req = q("SELECT `t`.`uid`, `t`.`state`, `s`.`title` FROM `assignes` as `a` JOIN `states` as `t` ON `a`.`uid` = `t`.`uid` JOIN `sources` as `s` ON `a`.`sid` = `s`.`sid` WHERE `a`.`sid` = '".mres($m['sid'])."' AND `a`.`state` = 1");
			if (empty($m['message'])) die(json_encode([
				"error" => 3,
				"message" => "Message is empty"
			]));
			if (mysqli_num_rows($req) == 0) {
				die(json_encode([
					"error" => 4,
					"message" => "No recipients for this source"
				]));
			} else while ($r = nr($req)) {
				$r['state'] = json_decode($r['state'], true);
				$data = [
					"chat_id" => $r['state']['chat_id'],
					"parse_mode" => 'HTML',
					"text" => 	$m['message']
				];
				if (DEBUG) file_put_contents('json_logs/__req.txt', json_encode($data).PHP_EOL.PHP_EOL, FILE_APPEND);
				$res = $tg->request("sendMessage", $data);
				if (DEBUG) file_put_contents('json_logs/__resp.txt', ($res).PHP_EOL, FILE_APPEND);
			}

			echo json_encode([
				"error" => 0,
				"message" => "Sent"
			]);
			break;
		default:
			// echo "Comming soon... or not";
			$temp_var = 'This page is not available! It will come back soon... or not :)';
			$ssr_tpl = require("./template.php");
			echo $ssr_tpl;
	}
?>
