#!/bin/bash
p=$0
dir="${p%/*}/lib"
php -d short_open_tag=false $dir/jpg2epub.php "$@"
