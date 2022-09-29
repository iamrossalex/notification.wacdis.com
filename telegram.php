<?php
	if (str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__) == $_SERVER['SCRIPT_NAME']) exit;
	// Test Key :: mgq8awgnsek7qmvowr2glns5cvtswtb8

	/**
	 * Docs: https://tlgrm.ru/docs/bots/api
	 * WebHook :: https://api.telegram.org/bot1761944399:AAEfjBJtOezl18I9tHs6FZ8FsEP5cn-14j8/setWebhook?url=https://notification.wacdis.com/@telegram
	 * Message Formatting :: https://core.telegram.org/api/entities | https://core.telegram.org/type/MessageEntity
	 */

	$m = file_get_contents('php://input');
	if (DEBUG) file_put_contents('json_logs/__hook.txt', ($m.PHP_EOL), FILE_APPEND);
	$m = json_decode($m,true);
	$user_id = $m['message']['from']['id'];
	$keyboards = [
		"main" => [
			[
				"Select source",
				"Connect",
				"Disconnect"
			],
			[
				"Show HELP",
				"Show API Reference"
			]
		],
		"source" => [
			[
				"Statictics"
			],
			[
				"Back"
			]
		]
	];

	require 'notification.class.php';

	$state = D::getUserState($user_id);
	$state['chat_id'] = $m['message']['chat']['id'];
	/***************************/
	$data = [
		"chat_id" => $m['message']['chat']['id'],
		"parse_mode" => 'HTML',
		"text" => ""
	];

	if ($state['current'] == 'SOURCE' && $m['message']['text'] === 'Turn off') {
		if ($state['is_online']) D::turnoff($state['source'], $user_id);
		$state['is_online'] = false;
		$m['message']['text'] = '';
	}
	if ($state['current'] == 'SOURCE' && $m['message']['text'] === 'Turn on') {
		if (!$state['is_online']) D::turnon($state['source'], $user_id);
		$state['is_online'] = true;
		$m['message']['text'] = '';
	}
	if ($state['current'] == "CONNECT" && $m['message']['text'] === 'Back') {$state['current'] = "MAIN"; $m['message']['text'] = '';}
	if ($state['current'] == "CONNECT" && $m['message']['text'] !== 'Back') {
		$sid = D::createSource($m['message']['text'], $user_id);
		$data["text"] .= 'Source connected. Source ID :: <code>' . $sid . "</code>. ";
		$state['current'] = "MAIN";
	}
	if ($state['current'] == "DISCONNECT" && $m['message']['text'] === 'Back') {$state['current'] = "MAIN"; $m['message']['text'] = '';}
	if ($state['current'] == "DISCONNECT" && $m['message']['text'] !== 'Back') {
		$sources = D::sources($user_id);
		if (in_array($m['message']['text'],$sources)) {
			$sid = D::deleteSource($m['message']['text'], $user_id);
			if ($sid) {
				$data["text"] .= "Source ".$sid." was disconnected. ";
				$m['message']['text'] = '';
			}
		} else {
			$data["text"] .= "<b>ERROR</b> :: Unknown source. ";
			$m['message']['text'] = 'Disconnect';
		}
		$state['current'] = "MAIN";
	}
	if ($state['current'] == "SELECT" && $m['message']['text'] === 'Back') {$state['current'] = "MAIN"; $m['message']['text'] = '';}
	if ($state['current'] == "SELECT" && $m['message']['text'] !== 'Back') {
		$sources = D::sources($user_id);
		if (in_array($m['message']['text'],$sources)) {
			$data["text"] .= "Source ".$m['message']['text']. " selected! ";
			list($state['source'], $state['is_online']) = D::getSource($m['message']['text'], $user_id);
			$state['current'] = "SOURCE";
		} else {
			$data["text"] .= "<b>ERROR</b> :: Unknown source name! ";
			$m['message']['text'] = 'Select source';
			$state['current'] == "MAIN";
		}
	}
	if ($state['current'] == 'SOURCE' && $m['message']['text'] === 'Back') {
		$state['current'] = "MAIN";
		$m['message']['text'] = '';
	}



	if ($state['current'] == 'SOURCE') {
		switch($m['message']['text']) {
			case "Statictics":
				// Drawing chars :: https://en.wikipedia.org/wiki/Box-drawing_character
				$data['text'] .= D::getSourceStats($state['source']);
				break;

			default:
				$data['text'] .= 'Waiting for command';
				if ($state['is_online'] == true) 
					$keyboards['source'][0][] = 'Turn off';
				else 
					$keyboards['source'][0][] = 'Turn on';
				$data["reply_markup"] = [
					"keyboard" => $keyboards['source'],
					"resize_keyboard" => true,
					"one_time_keyboard" => false
				];
				break;
		}
	}



	if ($state['current'] == 'MAIN') {
		switch ($m['message']['text']) {
			case "Select source":
				$sources = D::sources($user_id);
				if (sizeof($sources)>0) {
					$data["text"] .= "Select source";
					$keyb = [];
					$tmp = [];
					foreach ($sources as $src) {
						$tmp[] = $src;
						if (sizeof($tmp) == 2) {
							$keyb[] = $tmp;
							$tmp = [];
						}
					}
					if (sizeof($tmp) == 1) $keyb[] = $tmp;
					unset($tmp, $sources, $src);
					$keyb[] = ["Back"];
	
					$data["reply_markup"] = [
						"keyboard" => $keyb,
						"resize_keyboard" => true,
						"one_time_keyboard" => true
					];
					$state['current'] = "SELECT";
					break;
				} else {
					$data["text"] .= "Connect source first! ";
				}
				break;
			case "Connect":
				$data["text"] .= "Enter unique name of the source (example: \"mydomain.com\") or go back ";
				$state['current'] = "CONNECT";
				$data["reply_markup"] = [
					"keyboard" => [
						["Back"]
					],
					"resize_keyboard" => true,
					"one_time_keyboard" => true
				];
				break;
			case "Disconnect":
				$sources = D::sources($user_id);
				if (sizeof($sources)>0) {
					$data["text"] .= "Select source to disconnect: ";
					$keyb = [];
					$tmp = [];
					foreach ($sources as $src) {
						$tmp[] = $src;
						if (sizeof($tmp) == 2) {
							$keyb[] = $tmp;
							$tmp = [];
						}
					}
					if (sizeof($tmp) == 1) $keyb[] = $tmp;
					unset($tmp, $sources, $src);
					$keyb[] = ["Back"];
	
					$data["reply_markup"] = [
						"keyboard" => $keyb,
						"resize_keyboard" => true,
						"one_time_keyboard" => true
					];
					$state['current'] = "DISCONNECT";
					break;
				} else {
					$data["text"] .= "Nothing to disconnect! ";
				}
				break;
			case "Show HELP":
				$data['text'] .= 'Help docs are available at https://notification.wacdis.com/docs';
				break;
			case "Show API Reference":
				$tmpResp = $tg->request("deleteMessage", [
					"chat_id" => $m['message']['chat']['id'],
					"message_id" => $m['message']['message_id']
				]);
				if (DEBUG) file_put_contents('json_logs/__cmd.txt', (($tmpResp).PHP_EOL), FILE_APPEND);
				$data['text'] .= 'API Reference is available at https://notification.wacdis.com/docs/api';
				break;
			default:
				$data['text'] .= 'Waiting for command';
				$data["reply_markup"] = [
					"keyboard" => $keyboards['main'],
					"resize_keyboard" => true,
					"one_time_keyboard" => false
				];
				break;
		}
	}

	if (DEBUG) file_put_contents('json_logs/__req.txt', json_encode($data));
	$res = $tg->request("sendMessage", $data);
	if (DEBUG) file_put_contents('json_logs/__resp.txt', ($res));
	
	q("INSERT INTO `states` (`uid`, `stamp`, `state`) VALUES('".mres($user_id)."', ".(time()).", '".mres(json_encode($state))."') ON DUPLICATE KEY UPDATE `stamp`=".(time()).", `state`='".mres(json_encode($state))."'");
?>