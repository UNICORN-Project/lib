<?php
error_reporting(E_ALL & ~E_NOTICE);
$conf = array(
	kindlegen => "/Applications/kindlegen",
	imgconv =>"sips -Z %s --out %o",
	templates=>array(
		jisui_fix=>array(
			opf => "item/contents.opf",
			xhtml => "item/xhtml",
			images => "item/image",
			nav_temp => "item/nav.xhtml",
			p_temp =>"item/xhtml/p-temp.xhtml",
			cover_temp=>"item/xhtml/p-cover.xhtml",
			white_temp=>"item/xhtml/p-white.xhtml",
		),
		kindle_fix=>array(
			opf => "item/contents.opf",
			xhtml => "item/xhtml",
			images => "item/image",
			nav_temp => "item/nav.xhtml",
			p_temp =>"item/xhtml/p-temp.xhtml",
			white_temp=>"item/xhtml/p-white.xhtml",
			toc=>"item/toc.ncx"
		),
		reflow_tate=>array(
			opf => "item/contents.opf",
			xhtml => "item/xhtml",
			images => "item/image",
			nav_temp => "item/navigation-documents.xhtml",
			p_temp =>"item/xhtml/p-temp.xhtml",
			cover_temp=>"item/xhtml/p-cover.xhtml"
		
		)
	)
);