#!/usr/bin/env php
<?php
#$configs = include('config.php');
/**
* PHP send ebam 
*
*
* @author      Denis Davydov <dendem1980@yandex.ru>
* @version     1.0
*/
ini_set('memory_limit', '-1');
require 'inc/ebamclass.php';

$config = include('config2.php');

$ebam =new  Ebam();

	$ebam->table=$config['dbtable'];
	$ebam->dbhost=$config['dbhost'];
	$ebam->dbname=$config['database'];
	$ebam->dbport=$config['dbport'];
	$ebam->dbuser=$config['dbuser'];
	$ebam->dbpass=$config['dbpass'];
	$ebam->mask=$config['filemask'];
	$ebam->workdir=$config['workdir'];
	$ebam->normal=$config['normal'];
	$conn=$ebam->connectdb();

	$ebam->ereaddir();

	foreach($ebam->files as $file)
	{
	    echo $file."\n";
//	    $this->BulbOff();
	    $ebam->getEbamData($file);
	    $ebam->incertdb($conn,$ebam->all_rows);
//	    $ebam->bol=true;
	    $ebam->filemov($file);
	    unset($ebam->all_rows);
	}

	$ebam->closedb($conn);
unset($ebam);

?>
