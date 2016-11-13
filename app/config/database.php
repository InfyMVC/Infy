<?php

return array(
	'master' => array(
		/**
		* Currently supported: mysql
		*/
		'driver'     => 'mysql',

		/**
		* The host where connect to (can be an IP-address too)
		*/
		'hostname'   => 'localhost',

		/**
		* The port of the database if not default. To use the default set to 0
		*/
		'port'       => 3306,

		/**
		* Name of the user to connect with
		*/
		'user'       => 'root',

		/**
		* The password to use to authenticate the user
		*/
		'password'   => 'root',

		/**
		* The database to use
		*/
		'database'   => 'mysql',

		/**
		* If you need a custom charset u can define it here
		*/
		'charset'    => 'utf8',

		/**
		* Statements to execute when you connected to the server and selected the database
		*/
		'autoqueries'=> array(),

		/**
		* File to use (only when using a file based database
		*/
		'file'       => 'database.db',

		/**
		* Set to true if you don't want to write to the database
		*/
		'readonly'   => false),

	'slave' => array(
		0 => array(

			/**
			* Look above for examples
			*/
			'driver'     => 'mysql',

			'hostname'   => 'localhost',

			'port'       => 3306,

			'user'       => 'root',

			'password'   => 'root',

			'database'   => 'mysql',

			'charset'    => 'utf8',

			'autoqueries'=> array(),

			'file'       => 'database.db',

			'readonly'   => false))
);