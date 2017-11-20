<?php
/*******************************************************************************
* Utility to generate font definition files                                    *
*                                                                              *
* Version: 1.3                                                                 *
* Date:    2015-11-29                                                          *
* Author:  Olivier PLATHEY                                                     *
*******************************************************************************/

require('makefont-lib.php');

if(PHP_SAPI=='cli')
{
	// Command-line interface
	ini_set('log_errors', '0');
	if($argc==1)
		die("Usage: php makefont.php fontfile [encoding] [embed] [subset]\n");
	$fontfile = $argv[1];
	if($argc>=3)
		$enc = $argv[2];
	else
		$enc = 'cp1252';
	if($argc>=4)
		$embed = ($argv[3]=='true' || $argv[3]=='1');
	else
		$embed = true;
	if($argc>=5)
		$subset = ($argv[4]=='true' || $argv[4]=='1');
	else
		$subset = true;
	MakeFont($fontfile, $enc, $embed, $subset);
}
?>
