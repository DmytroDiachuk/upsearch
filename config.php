<?php

$settings['database']['tablePos_name']='tablePos';
$settings['database']['search_full_name'] ='search_full';
$settings['database']['params']['host']='localhost';
$settings['database']['params']['username']='root';
$settings['database']['params']['password']='sss';
$settings['database']['params']['dbname']='test';
$settings['database']['params']['charset']='utf8';
$settings['rowsGetCount']=2000;
$settings['fieldsList']=array('field1','field2','field3');;
/*
 * CREATE TABLE IF NOT EXISTS tablePos (
    inc int(8) unsigned NOT NULL AUTO_INCREMENT,
    increment int(8) NOT NULL,
    ratings varchar(6) NOT NULL,
    goroda varchar(30) NOT NULL,
    field1 text NOT NULL,
    field2 text NOT NULL,
    field3 text NOT NULL,
    PRIMARY KEY (inc)
)  ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
 */