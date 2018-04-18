#!/bin/sh                                                                       

svn up
php bin/php/ezpgenerateautoloads.php
php bin/php/ezcache.php --clear-all

rm var/auction/cache/override/*
rm var/clearcap/cache/override/*
