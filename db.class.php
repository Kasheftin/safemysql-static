<?php
/**
 * @author kasheftin@gmail.com
 * Static wrapper for SafeMySQL class with some method aliases.
 * Keeps array of SafeMySQL objects and proxies static calls to the object call of choosen SafeMySQL object.
 */

class DB {

	/** Array with SafeMySQL objects */
	static protected $dbs = [];

	/** Current connection identifier **/
	static protected $cid = null;

	/**
	 * Config with connections data, each item thrown to the SafeMySQL constructor during the first call of the choosen connection.
	 * The only exception is `default` property - in case if there are several possible connections it's choosen by default.
	 * [
	 * 	"myconnection1" => [
	 * 		"user" => "user1",
	 * 		"pass" => "pass1",
	 * 		"db" => "db1",
	 * 		"default" => 1
	 * 	],
	 * 	"myconnection2" => [
	 * 		"user" => "user1",
	 * 		"pass" => "pass1",
	 * 		"db" => "db1"
	 * 	]
	 * ]
	 */
	static protected $config = [];

	static public function setConfig($config) {
		static::$config = $config;
	}

	static public function set($cid) {
		static::$cid = $cid;
	}

	static public function get($cid=null) {
		if (!$cid) $cid = static::$cid;
		if (!$cid) {
			if (count(static::$config)==1) {
				$cid = key(static::$config);
			}
			elseif (count(static::$config)>1) {
				foreach(static::$config as $i => $rw) {
					if ($rw["default"]) {
						$cid = $i;
						break;
					}
				}
			}
		}
		if (!$cid) throw new Exception("Connection id is not specified and is not defined by config.");
		if (!array_key_exists($cid,static::$dbs)) {
			if (!array_key_exists($cid,static::$config)) throw new Exception("Connection with id ".$cid." was not found in config.");
			static::$dbs[$cid] = new SafeMySQL(static::$config[$cid]);
		}
		return static::$dbs[$cid];
	}

	static public function __callStatic($name,$arguments) {
		$aliases = [
			"q" => "query",
			"f" => "getall",
			"f1" => "getrow"
		];
		$name = strtolower($name);
		if (array_key_exists($name,$aliases)) $name = $aliases[$name];
		$db = static::get();
		if (method_exists($db,$name)) {
			return call_user_func_array([$db,$name],$arguments);
		}
		throw new Exception("Method ".$name." was not found in SafeMySQL class.");
	}
}
