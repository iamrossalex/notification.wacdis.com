<?php

class D {
	public static function getUserState($uid) {
		$state = q1("SELECT `state` FROM `states` WHERE `uid`='".mres($uid)."'")['state'] ?? '{"current": "MAIN"}';
		$state = json_decode($state, true);
		return $state;
	}
	public static function sources($uid) {
		$rq = q("SELECT `a`.`sid` as `sid`, `s`.`title` as `title` FROM `assignes` as `a` JOIN `sources` as `s` ON `a`.`sid` = `s`.`sid` WHERE `a`.`uid`='".mres($uid)."'");
		while($r = nr($rq)) $sids[] = $r['title'];
		return $sids;
	}
	public static function getSource($title, $uid) {
		$sid = q1("SELECT `a`.`sid` as `sid`, `a`.`state` as `state` FROM `assignes` as `a` JOIN `sources` as `s` ON `a`.`sid` = `s`.`sid` WHERE `s`.`title`='".$title."' AND `a`.`uid`='".$uid."'");
		if (!empty($sid['sid'])) return [$sid['sid'],$sid['state']]; else return false;
	}
	public static function createSource($title, $uid) {
		// $is = q1("SELECT ")
		$sid = strtolower(passgen(32));
		q("INSERT INTO `sources` VALUES ('".$sid."','".mres($title)."')");
		q("INSERT INTO `assignes` VALUES ('".$sid."','".$uid."',1,1)");
		return $sid;
	}
	public static function deleteSource($title, $uid) {
		$sid = q1("SELECT `a`.`sid` as `sid` FROM `assignes` as `a` JOIN `sources` as `s` ON `a`.`sid` = `s`.`sid` WHERE `s`.`title`='".$title."' AND `a`.`uid`='".$uid."'", 'sid');
		if (!empty($sid)) {
			q("DELETE FROM `assignes` WHERE `sid`='".$sid."'");
			q("DELETE FROM `sources` WHERE `sid`='".$sid."'");
			// Нужно еще удалять операционные логи и статы
			return $sid;
		} else {
			return false;
		}
	}
	public static function getSourceStats($sid) {
		// https://en.wikipedia.org/wiki/Box-drawing_character
		if (DEBUG && $sid != 'mgq8awgnsek7qmvowr2glns5cvtswtb8') {
			return "Statistics is disabled now.";
		}
		$data = "<code>";
		$data .= "┌────────────────────────────┐" . PHP_EOL;
		$data .= "│ Stats                      │" . PHP_EOL;
		$data .= "├───────────┬────────────────┤" . PHP_EOL;
		$data .= "│ Day       │ Value          │" . PHP_EOL;
		$data .= "├───────────┼────────────────┤" . PHP_EOL;
		$data .= "│ Today     │ 3              │" . PHP_EOL;
		$data .= "│ 24h       │ 9              │" . PHP_EOL;
		$data .= "│ Yesterday │ 12             │" . PHP_EOL;
		$data .= "│ This Week │ 166            │" . PHP_EOL;
		$data .= "│ 7 days    │ 180            │" . PHP_EOL;
		$data .= "└───────────┴────────────────┘" . PHP_EOL;
		$data .= "</code>" . PHP_EOL;
		return $data;
	}
	public static function turnoff($sid, $uid) {
		q("UPDATE `assignes` SET `state` = 0 WHERE `sid`='".$sid."' AND `uid`='".$uid."'");
		return true;
	}
	public static function turnon($sid, $uid) {
		q("UPDATE `assignes` SET `state` = 1 WHERE `sid`='".$sid."' AND `uid`='".$uid."'");
		return true;
	}
}
