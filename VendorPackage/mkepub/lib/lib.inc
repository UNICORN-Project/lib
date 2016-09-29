<?php
function getmeta($src,$opts) {
	$fn =  iconv("UTF-8-MAC", "UTF-8", $src['filename']);
	if(preg_match("/[\[|\(](.+)[\]|\)](.+)/",$fn,$a)) {
		$meta['author'] = $a[1] ;
		$meta['title'] = $a[2] ;
	} else {
		$meta['title'] = $fn ;
		$meta['author'] = "unknown" ;
	}
	$meta['hash'] = md5($meta['author'].$meta['title']) ;
	date_default_timezone_set('Asia/Tokyo');
	$meta['mdate'] = date("Y-m-d\TH:i:s\Z",time()); 


	$meta['page_dir'] = ($opts['l'])?"ltr":"rtl" ;
	if(!$opts['p']) $meta['padding'] = array('lr'=>($opts['l'])?"page-spread-left":"page-spread-right") ;
	
	if($opts['o']) {
		$td = pathinfo($opts['o']) ;
		if(!file_exists($td['dirname'])) err("cannot create target") ;
		$meta['oname'] = (($td['dirname']==".")?$src['dirname']:$td['dirname'])."/".$td['filename'] ;
	} else {
		$meta['oname'] = $src['dirname']."/".$src['filename'];
	}
	$meta['tdir'] = $meta['oname']."_epub" ;
	$meta['oname'] = $meta['oname'].(($opts['k'])?".kepub":"").".epub";
	return $meta ;
}

function showmeta($src,$meta) {
	echo "input :".$src['filename']."\n";
	echo "  title: ".$meta['title']."\n";
	echo "  author: ".$meta['author']."\n";
	echo "output: ".$meta['oname']."\n" ;
}

function err($msg) {
	echo "ERROR!!\n";
	echo $msg."\n\n" ;
	exit(-1) ;
}

function mkfn($sfx,$n) {
	$nn = substr("0000".$n,-4) ;
	return $sfx.$nn ;
}


function parseParameters($noopt = array()) {
    $result = array();
    $params = $GLOBALS['argv'];
    // could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
    reset($params);
    while (list($tmp, $p) = each($params)) {
        if ($p{0} == '-') {
            $pname = substr($p, 1);
            $value = true;
            if ($pname{0} == '-') {
                // long-opt (--<param>)
                $pname = substr($pname, 1);
                if (strpos($p, '=') !== false) {
                    // value specified inline (--<param>=<value>)
                    list($pname, $value) = explode('=', substr($p, 2), 2);
                }
            }
            // check if next parameter is a descriptor or a value
            $nextparm = current($params);
            if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
            $result[$pname] = $value;
        } else {
            // param doesn't belong to any option
            $result[] = $p;
        }
    }
    return $result;
}