<?php

error_reporting(E_ALL & ~E_NOTICE);

// jpg2epub
//    version 1.4
//    by @wakufactory

if (function_exists("mb_internal_encoding")){
	@mb_internal_encoding("UTF-8") ;
}
require_once("htmltemplate.inc") ;
require_once("lib.inc"); 

$apppath = pathinfo($argv[0]) ;
include $apppath['dirname']."/conf.php" ;
$opts = parseParameters() ;
echo "mkepub ver 1.4\n" ;
if($opts[1]=="") usage() ;

$src = pathinfo($opts[1]) ;
if($src['dirname']==".") $src['dirname'] = getcwd() ;
$meta = getmeta($src,$opts) ;
if (isset($argv[2]) && 0 < strlen($argv[2])){
	$meta['title'] = iconv("UTF-8-MAC", "UTF-8", $argv[2]);
	if (0 == strlen($meta['title'])){
		$meta['title'] = $argv[2];
	}
}
if (isset($argv[3]) && 0 < strlen($argv[3])){
	$meta['author'] = iconv("UTF-8-MAC", "UTF-8", $argv[3]);
	if (0 == strlen($meta['author'])){
		$meta['author'] = $argv[3];
	}
}
if (isset($argv[4]) && 0 < strlen($argv[4])){
	$meta['publisher'] = iconv("UTF-8-MAC", "UTF-8", $argv[4]);
	if (0 == strlen($meta['publisher'])){
		$meta['publisher'] = $argv[4];
	}
}
if (isset($argv[5]) && 0 < strlen($argv[5])){
	$meta['purchaser'] = iconv("UTF-8-MAC", "UTF-8", $argv[5]);
	if (0 == strlen($meta['purchaser'])){
		$meta['purchaser'] = $argv[5];
	}
}

if($opts['a']) $meta['kindle'] = array("kindle"=>1) ;

$meta['tempname'] = ($meta['kindle'])?"kindle_fix":"jisui_fix" ;
showmeta($src,$meta) ;

$tempconf = $conf['templates'][$meta['tempname']] ;

$srcdir = $src['dirname']."/".$src['filename'] ;
$img = getimgs($srcdir ) ;

if(count($img)==0) err('no images') ;

//copy template
$tdir = $meta['tdir'] ;
if(file_exists($tdir)) system("rm -r \"$tdir\"") ;
system("cp -Rf '".$apppath['dirname']."/temp/".$meta['tempname']."' '$tdir'") ;


//copy images
$page = array() ;
$iton = array() ;
$imgdir = $tdir."/".$tempconf['images'] ;
$lr = ($meta['page_dir']=="rtl" xor $meta['padding']==null)?1:0 ;
$cover = array_shift($img) ;
$cover['vp_w'] = $cover['w'] ;
$cover['vp_h'] = $cover['h'] ;
$cover['img_w'] = $cover['w'] ;
$cover['img_h'] = $cover['h'] ;
$cover['viewbox']="0 0 ".$cover['w']." ".$cover['h'];
$cover['img_name'] = "cover.".$cover['ext'] ;
$iton[$cover['name']] = "p-cover.xhtml" ;

if($meta['kindle']) {
	$meta['kindle']['size']=$cover['img_w']."x".$cover['img_h'] ;
}

copy($srcdir."/".$cover['name'],$imgdir."/".$cover['img_name']) ;


$mag = 1.0 ;
$mside = 0;
$mtop = 0 ;
if($opts['z']) {
	$mag =  floatval($opts['z'])/100.0 ;
	if($opts['t']!==null) $mtop =  floatval($opts['t'])/100.0 ;
	else $mtop = ($mag-1.0)/2 ;
	if($opts['s']!==null) $mside =  floatval($opts['s'])/100.0 ;
	else $mside = ($mag-1.0)/2 ;	
}

foreach($img as $x =>$i) {
	$imgid = mkfn("img",$x) ;
	$img[$x]['id'] = $imgid ;
	$img[$x]['img_name'] = $imgid.".".$i['ext'] ;
	$iton[$i['name']] = mkfn("p",$x).".xhtml" ;

	$gw = $i['w']*$mag  ;
	$gh = $i['h']*$mag ;
	$ml = $i['w']*(($lr==1)?$mside:($mag-1.0-$mside)) ;
	$mt = $i['h']*$mtop ;
	$page[$x] = array(
		'id'=>"page".$x,
		'xhtml'=>mkfn("p",$x).".xhtml",
		'img_id'=>	$imgid,
		'img_name'=>$imgid.".".$i['ext'],
		'lr' => ($lr==0)?"page-spread-right":"page-spread-left",
		'vp_w'=>$i['w'],
		'vp_h'=>$i['h'],
		'img_w'=>$gw,
		'img_h'=>$gh,
		'viewbox'=>"$ml $mt ".($i['w'])." ".($i['h']),
		'title'=>"title"
	);
	if(!$meta['kindle']) $page[$x]['svg'] = "svg" ;
	$lr ^= 1 ;
	copy($srcdir."/".$i['name'],"$imgdir/$imgid.".$i['ext']) ;
}

$temp = new HtmlTemplate() ;

$args = $meta ;
$args['pages'] = $page ;
$args['images'] = $img ;
$args['cover'] = $cover ;
$args['authors'] = array( array('name'=>$meta['author'],'seq'=>1,'id'=>"creator01")) ;

//make opf
$opfpath = $tdir."/".$tempconf['opf'] ;
$opf = $temp->t_buffer($opfpath,$args) ;
$fp = fopen($opfpath,"w") ;
fputs($fp,$opf) ;
fclose($fp);

//make navi
$navi =  getnavi($srcdir."/navi.txt",$iton) ;
if($navi==null) {	//default navi
	$navi = array(
		array('text'=>表紙,'page'=>"p-cover.xhtml"),
		array('text'=>本文,'page'=>$page[0]['xhtml']),
		array('text'=>最終ページ,'page'=>$page[count($page)-1]['xhtml'])
	);
}
if($meta['kindle']) {
	array_shift($navi);
	for($i=0;$i<count($navi);$i++) $navi[$i]['order'] = $i+1;
}
if($tempconf['nav_temp']) {
	$navpath = $tdir."/".$tempconf['nav_temp'] ;
	$nav = $temp->t_buffer($navpath,array('navi'=>$navi)) ;
	$fp = fopen($navpath,"w") ;
	fputs($fp,$nav) ;
	fclose($fp);
}
/*
//make ncx
if($meta['kindle']) {
	$tocpath = $tdir."/".$tempconf['toc'] ;
	$toc = $temp->t_buffer($tocpath,array('navi'=>$navi)) ;
	$fp = fopen($tocpath,"w") ;
	fputs($fp,$toc) ;
	fclose($fp);	
}
*/

//make cover
if($tempconf['cover_temp']) {
	$coverpath = $tdir."/".$tempconf['cover_temp'] ;
	$cov = $temp->t_buffer($coverpath,$cover) ;
	$fp = fopen($coverpath,"w") ;
	fputs($fp,$cov) ;
	fclose($fp);
}

if($meta['padding']) {
	$whitepath = $tdir."/".$tempconf['white_temp'] ;
	$white = $temp->t_buffer($whitepath,$page[0]) ;
	$fp = fopen($whitepath,"w") ;
	fputs($fp,$white) ;
	fclose($fp);	
}

//make pages
$tmppath = $tdir."/".$tempconf['p_temp'] ;
$pagepath = $tdir."/".$tempconf['xhtml'] ;
foreach($page as $i=>$p) {
	$page = $temp->t_buffer($tmppath,$p) ;
	$fp = fopen($pagepath."/".$p['xhtml'],"w") ;
	fputs($fp,$page) ;
	fclose($fp);	
}
unlink($tmppath);

echo "make epub\n" ;
@unlink($meta['oname']) ;
chdir($meta['tdir']) ;
system("zip -0 -X -q '".$meta['oname']."' mimetype" ) ;
system("zip -r -q '".$meta['oname']."' * -x mimetype -x .* -x */.* -x */*/.*") ;


if(isset($meta['kindle'])) {
	echo "make mobi\n";
	system($conf['kindlegen'].' "'.$meta['oname'].'"');
}

echo "Completed!\n" ;


function getimgs($path) {
	$fp = @opendir($path) ;
	if(!$fp) return null ;
	$f = array() ;
	while(($l = readdir($fp)) !==false ) {
		if(preg_match("/^\./",$l) || !preg_match("/\.(jpe?g|png)$/i",$l,$a)) continue;
		$s = getimagesize($path."/".$l) ;
		$f[] = array(
			'name'=>$l,
			'w'=>$s['0'],'h'=>$s[1],
			'ext'=>$a[1],
			'type'=>(strtolower($a[1])=="png")?"image/png":"image/jpeg"
		);
	}
	array_multisort(array_column($f, 'name'), SORT_ASC, $f);
	return $f ;
}

function getnavi($path,$iton) {
	$fp = @fopen($path,"r") ;
	if(!$fp) return null ;
	$f = array() ;
	while(($l = fgets($fp)) !==false ) {
		$ll = explode("\t",rtrim($l)) ;
		$f[] = array(
			'page'=>$iton[$ll[1]],
			'text'=>$ll[0],
		);
	}
	return $f ;
}

function usage() {
	echo "usage jpg2epub.php <srcdir> [-p] [-l] [-k] [-a] [-r <size>] [-z <zoom>] [-t <top margin>] [-s <side margin>] [-o <outfile>]\n" ;
	exit(0) ;
}


