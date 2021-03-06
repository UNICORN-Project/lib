<?php

class ProjectManager
{
	public static function dispatchProjectConfig($argProjectName){
		if ('' === $argProjectName || 0 >= strlen($argProjectName)){
			return;
		}
		// Nginxで稼働中の場合はconfを探して書き換える
		if (FALSE !== strpos($_SERVER['HTTP_HOST'], 'localhost') && 0 === strpos($_SERVER['HTTP_HOST'], 'fwm') && FALSE !== strpos($_SERVER['SERVER_SOFTWARE'], 'nginx')){
			// ローカルサーバかどうか
			if (is_file('/Applications/MAMP/conf/nginx/nginx.conf')){
				// ローカルNginxなので、confを自動生成して上げる
				if (0 < strpos($argProjectName, 'Package')){
					$argProjectName = substr($argProjectName, 0, strlen($argProjectName) - 7);
				}
				$conName = PROJECT_NAME.'Configure';
				// Nginx用の設定
				if (!is_dir('/Applications/MAMP/conf/nginx/conf.d')){
					echo 'mkdir -m 777 /Applications/MAMP/conf/nginx/conf.d'.PHP_EOL;
					@mkdir('/Applications/MAMP/conf/nginx/conf.d', 0777, TRUE);
				}
				$confSuppleBasePath = dirname(dirname($conName::PROJECT_ROOT_PATH));
				$pjdirname = basename($confSuppleBasePath);
				if (!is_file('/Applications/MAMP/conf/nginx/conf.d/nginx-mamp-'.$argProjectName.'.conf')){
					// 設定を書き換えてリンクを貼る
					@copy($confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-fwm.conf', $confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$argProjectName.'.conf');
					file_put_contents($confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$argProjectName.'.conf', str_replace(array('/UNICORN/lib/FrameworkManager/sample/packages/ProjectPackage/', '/UNICORN/lib/FrameworkManager/template/managedocs', corefilename()), array('/'.$pjdirname.'/'.basename(dirname($conName::PROJECT_ROOT_PATH)).'/'.$argProjectName.'Package/', '/'.$pjdirname.'/lib/FrameworkManager/template/managedocs', strtolower($argProjectName)), file_get_contents($confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$argProjectName.'.conf')));
					@exec('chmod -R 0777 ' .$confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$argProjectName.'.conf');
					@exec('ln -s '.$confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$argProjectName.'.conf /Applications/MAMP/conf/nginx/conf.d/nginx-mamp-'.$argProjectName.'.conf');
				}
				if (FALSE === strpos(@file_get_contents('/etc/hosts'), strtolower($argProjectName).'api.localhost')){
					@exec('sed -i "" -e $\'1s/^/127.0.0.1       '.strtolower($argProjectName).'api.localhost \\\\\\n/\' /etc/hosts');
				}
				if (FALSE === strpos(@file_get_contents('/etc/hosts'), strtolower($argProjectName).'.localhost')){
					@exec('sed -i "" -e $\'1s/^/127.0.0.1       '.strtolower($argProjectName).'.localhost \\\\\\n/\' /etc/hosts');
				}
				if (FALSE === strpos(@file_get_contents('/etc/hosts'), 'fwm'.strtolower($argProjectName).'.localhost')){
					@exec('sed -i "" -e $\'1s/^/127.0.0.1       fwm'.strtolower($argProjectName).'.localhost \\\\\\n/\' /etc/hosts');
				}
				// Nginx再起動
				@exec('sudo /Applications/MAMP/Library/bin/nginxctl -s reload');
				sleep(10);
				// 						@exec('sudo sh /Applications/MAMP/bin/startNginx.sh');
				// 						sleep(5);
			}
			// XXX リモートサーバの場合の自動設定などをする場合はココに追記
		}
		return TRUE;
	}

	public static function createProject($argProjectName='', $argProjectDisplayName='', $argProjectUser='', $argProjectGroup='', $argIOSEnabled=TRUE, $argAndroidEnabled=TRUE){
		$conName = PROJECT_NAME.'Configure';
		debug('$argProjectName='.$argProjectName);
		$samplePackage = $conName::SAMPLE_PROJECT_PACKAGE_PATH;
		$newProjectName = str_replace('Package', '', ucfirst($argProjectName.basename($samplePackage)));
		$pjdirname = basename(dirname(dirname($conName::PROJECT_ROOT_PATH)));
		debug('$newProjectName='.$newProjectName);
		// 移動先のパス
		$movePath = dirname($conName::PROJECT_ROOT_PATH).'/'.$newProjectName.'Package';
		if (!is_dir($movePath)){
			debug('$movePath='.$movePath);
			if(!dir_copy($samplePackage, $movePath, 0777)){
				return FALSE;
			}
			// プロジェクト名が指定されている場合は、デフォルトの定義を書き換えて上げる為の処理
			if('' !== $argProjectName){
				// config.xmlのファイル名を書き換える
				$newConfigXMLPath = $movePath.'/core/' . $newProjectName . '.config.xml';
				rename($movePath . '/core/Project.config.xml', $newConfigXMLPath);
				// package.xmlのファイル名を書き換える
				rename($movePath . '/core/Project.package.xml', $movePath.'/core/' . $newProjectName . '.package.xml');
				// config.xml内のプロジェクト名を書き換える
				$configXMLStr = file_get_contents($newConfigXMLPath);
				$configXMLStr = str_replace(array('<Project>', '</Project>'), array('<'.$newProjectName.'>', '</'.$newProjectName.'>'), $configXMLStr);
				// 新しい定義で書き換え
				file_put_contents($newConfigXMLPath, $configXMLStr);
				// 重いのでコマメにunset
				unset($configXMLStr);
				// .projectpackageファイルに表示プロジェクト名を入れる
				file_put_contents($movePath.'/.projectpackage', $argProjectDisplayName);
				// index内のプロジェクト名を書き換える
				$_webdocsIndexPath = $movePath.'/apidocs/index.php';
				$_indexStr = file_get_contents($_webdocsIndexPath);
				$_indexStr = str_replace('$projectpkgName = "Project";', '$projectpkgName = "'.ucfirst($newProjectName).'";', $_indexStr);
				$_indexStr = str_replace('$fwpath = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))))."/lib/FrameworkPackage";', '$fwpath = dirname(dirname(dirname(dirname(__FILE__))))."/lib/FrameworkPackage";', $_indexStr);
				// 新しい定義で書き換え
				file_put_contents($_webdocsIndexPath, $_indexStr);
				// 重いのでコマメにunset
				unset($_indexStr);
				$_webdocsIndexPath = $movePath.'/webdocs/index.php';
				$_indexStr = file_get_contents($_webdocsIndexPath);
				$_indexStr = str_replace('$projectpkgName = "Project";', '$projectpkgName = "'.ucfirst($newProjectName).'";', $_indexStr);
				$_indexStr = str_replace('$fwpath = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))))."/lib/FrameworkPackage";', '$fwpath = dirname(dirname(dirname(dirname(__FILE__))))."/lib/FrameworkPackage";', $_indexStr);
				// 新しい定義で書き換え
				file_put_contents($_webdocsIndexPath, $_indexStr);
				// 重いのでコマメにunset
				unset($_indexStr);
				// Nginxで稼働中の場合はconfを探して書き換える
				if (FALSE !== strpos($_SERVER['HTTP_HOST'], 'localhost') && 0 === strpos($_SERVER['HTTP_HOST'], 'fwm') && FALSE !== strpos($_SERVER['SERVER_SOFTWARE'], 'nginx')){
					// ローカルサーバかどうか
					if (is_file('/Applications/MAMP/conf/nginx/nginx.conf')){
						// ローカルNginxなので、confを自動生成して上げる
						// Nginx用の設定
						if (!is_dir('/Applications/MAMP/conf/nginx/conf.d')){
							echo 'mkdir -m 777 /Applications/MAMP/conf/nginx/conf.d'.PHP_EOL;
							@mkdir('/Applications/MAMP/conf/nginx/conf.d', 0777, TRUE);
						}
						$confSuppleBasePath = dirname(dirname($conName::PROJECT_ROOT_PATH));
						if (!is_file('/Applications/MAMP/conf/nginx/conf.d/nginx-mamp-'.$newProjectName.'.conf')){
							// 設定を書き換えてリンクを貼る
							@copy($confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-fwm.conf', $confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$newProjectName.'.conf');
							file_put_contents($confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$newProjectName.'.conf', str_replace(array('/UNICORN/lib/FrameworkManager/sample/packages/ProjectPackage/', corefilename()), array('/'.$pjdirname.'/'.basename(dirname($conName::PROJECT_ROOT_PATH)).'/'.$newProjectName.'Package/', strtolower($newProjectName)), file_get_contents($confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$newProjectName.'.conf')));
							@exec('chmod -R 0777 ' .$confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$newProjectName.'.conf');
							@exec('ln -s '.$confSuppleBasePath.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$newProjectName.'.conf /Applications/MAMP/conf/nginx/conf.d/nginx-mamp-'.$newProjectName.'.conf');
						}
						if (FALSE === strpos(@file_get_contents('/etc/hosts'), strtolower($newProjectName).'api.localhost')){
							@exec('sed -i "" -e $\'1s/^/127.0.0.1       '.strtolower($newProjectName).'api.localhost \\\\\\n/\' /etc/hosts');
						}
						if (FALSE === strpos(@file_get_contents('/etc/hosts'), strtolower($newProjectName).'.localhost')){
							@exec('sed -i "" -e $\'1s/^/127.0.0.1       '.strtolower($newProjectName).'.localhost \\\\\\n/\' /etc/hosts');
						}
						if (FALSE === strpos(@file_get_contents('/etc/hosts'), 'fwm'.strtolower($newProjectName).'.localhost')){
							@exec('sed -i "" -e $\'1s/^/127.0.0.1       fwm'.strtolower($newProjectName).'.localhost \\\\\\n/\' /etc/hosts');
						}
						// 各種ローカル用のBASE＿URLを自動書き換え
						$configXMLStr = file_get_contents($newConfigXMLPath);
						$configXMLStr = str_replace(array('<BASE_URL stage="local">http://localhost/</BASE_URL>', '<APPAPI_BASE_URL stage="local">http://localhost/</APPAPI_BASE_URL>'), array('<BASE_URL stage="local">https://'.strtolower($newProjectName).'.localhost/</BASE_URL>', '<APPAPI_BASE_URL stage="local">https://'.strtolower($newProjectName).'api.localhost/</APPAPI_BASE_URL>'), $configXMLStr);
						// 新しい定義で書き換え
						file_put_contents($newConfigXMLPath, $configXMLStr);
						// ローカル開発用に自己証明書を設置
						if (!is_dir('/Applications/MAMP/.ssl')){
							@mkdir('/Applications/MAMP/.ssl', 0777, TRUE);
						}
						if (!is_file('/Applications/MAMP/.ssl/self-server.crt')){
							echo $confSuppleBasePath.'/supple/setting/NginxWithMAMP/.ssl/self-server.crt';
							@copy($confSuppleBasePath.'/supple/setting/NginxWithMAMP/.ssl/self-server.crt', '/Applications/MAMP/.ssl/self-server.crt');
							@copy($confSuppleBasePath.'/supple/setting/NginxWithMAMP/.ssl/self-server.key', '/Applications/MAMP/.ssl/self-server.key');
						}
						if (FALSE === strpos(@file_get_contents('/Applications/MAMP/conf/nginx/nginx.conf'), 'conf.d/*.conf')){
							// 元のconf移動
							@rename('/Applications/MAMP/conf/nginx/nginx.conf', '/Applications/MAMP/conf/nginx/nginx.conf.org');
							// conf入れ替え
							@copy($confSuppleBasePath.'/supple/setting/NginxWithMAMP/nginx.conf', '/Applications/MAMP/conf/nginx/nginx.conf');
						}
						// Nginx再起動
						@exec('sudo /Applications/MAMP/Library/bin/nginxctl -s reload');
// 						sleep(10);
// 						@exec('sudo sh /Applications/MAMP/bin/startNginx.sh');
// 						sleep(5);
					}
					// Vagrant環境かどうか
					else if (is_dir('/var/www/release/.vagrant') && 0 === strpos($_SERVER['HTTP_HOST'], 'fwm')) {
						// Vagrant開発環境なので、Vagrant.shしたときの自動hosts設定を前提にしたURL補正処理を実施
						$configXMLStr = file_get_contents($newConfigXMLPath);
						$configXMLStr = str_replace(array('<BASE_URL stage="local">http://localhost/</BASE_URL>', '<APPAPI_BASE_URL stage="local">http://localhost/</APPAPI_BASE_URL>'), array('<BASE_URL stage="local">https://web'.substr($_SERVER['HTTP_HOST'], 3).'/</BASE_URL>', '<APPAPI_BASE_URL stage="local">https://api'.substr($_SERVER['HTTP_HOST'], 3).'/</APPAPI_BASE_URL>'), $configXMLStr);
						file_put_contents($newConfigXMLPath, $configXMLStr);
					}
					// XXX それ以外の場合はリモートサーバの場合としている
					else if (0 === strpos($_SERVER['HTTP_HOST'], 'fwm')) {
						$configXMLStr = file_get_contents($newConfigXMLPath);
						$configXMLStr = str_replace(array('<BASE_URL stage="local">http://localhost/</BASE_URL>', '<APPAPI_BASE_URL stage="local">http://localhost/</APPAPI_BASE_URL>'), array('<BASE_URL stage="local">https://web'.substr($_SERVER['HTTP_HOST'], 3).'/</BASE_URL>', '<APPAPI_BASE_URL stage="local">https://api'.substr($_SERVER['HTTP_HOST'], 3).'/</APPAPI_BASE_URL>'), $configXMLStr);
						file_put_contents($newConfigXMLPath, $configXMLStr);
					}
				}
				// XXX Nginx以外のWebサーバーの場合の振り分けはココに追加
				else {
					
				}
			}
		}
		// iOSサンプルのコピー
		if (1 === (int)$argIOSEnabled && !is_dir($movePath.'/iOSProject')){
			if(!dir_copy(dirname($samplePackage).'/iOSProject', $movePath.'/iOSProject', 0777)){
				return FALSE;
			}
			// バージョンファイル生成
			touch($movePath.'/.ios');
			@file_put_contents($movePath.'/.ios', '1.0.000');
		}
		// Androidサンプルのコピー
		if (1 === (int)$argAndroidEnabled && !is_dir($movePath.'/AndroidProject')){
			if(!dir_copy(dirname($samplePackage).'/AndroidProject', $movePath.'/AndroidProject', 0777)){
				return FALSE;
			}
			// バージョンファイル生成
			touch($movePath.'/.android');
			@file_put_contents($movePath.'/.android', '1.0.000');
		}
		// パーミッションと所有権の変更
		@exec('chmod -R 0777 '.$movePath);
		@exec('chmod -R 0777 '.$webdocsPath);
		if (0 < strlen($argProjectUser)){
			if(0 < strlen($argProjectGroup)){
				@exec('chown -R '.$argProjectUser.':'.$argProjectGroup.' ' .$movePath);
				@exec('chown -R '.$argProjectUser.':'.$argProjectGroup.' ' .$webdocsPath);
			}
			else {
				@exec('chown -R '.$argProjectUser.' ' .$movePath);
				@exec('chown -R '.$argProjectUser.' ' .$webdocsPath);
			}
		}
		// XXX iOS用の処理
		if (1 === (int)$argIOSEnabled){
			@chmod($movePath.'/.ios', 0666);
			// iOSサンプル内のプロジェクト内のローカルのRESTfulAPIの向け先を変える
			$iosdefineStr = file_get_contents($movePath.'/iOSProject/Project/SupportingFiles/define.h');
			if (FALSE !== strpos($_SERVER['HTTP_HOST'], 'localhost') && 0 === strpos($_SERVER['HTTP_HOST'], 'fwm') && FALSE !== strpos($_SERVER['SERVER_SOFTWARE'], 'nginx')){
				// おそらくNginxの場合
				if ('443' === $_SERVER['SERVER_PORT']){
					// プロトコル指定を書き換え
					$iosdefineStr = str_replace('#   define PROTOCOL @"http"', '#   define PROTOCOL @"https"', $iosdefineStr);
				}
				// ローカルサーバかどうか
				if (is_file('/Applications/MAMP/conf/nginx/nginx.conf')){
					// ドメイン指定を書き換え
					$iosdefineStr = str_replace('#   define DOMAIN_NAME @"localhost"', '#   define DOMAIN_NAME @"'.strtolower($newProjectName).'api.localhost"', $iosdefineStr);
					// URL_BASEを書き換え
					$iosdefineStr = str_replace('#   define URL_BASE @"/workspace/UNICORN-project/lib/FrameworkManager/sample/packages/ProjectPackage/apidocs/"', '#   define URL_BASE @"/"', $iosdefineStr);
				}
				// Vagrant環境かどうか
				else if (is_dir('/var/www/release/.vagrant') && 0 === strpos($_SERVER['HTTP_HOST'], 'fwm')) {
					// ドメイン指定を書き換え
					$iosdefineStr = str_replace('#   define DOMAIN_NAME @"localhost"', '#   define DOMAIN_NAME @"api'.substr($_SERVER['HTTP_HOST'], 3).'"', $iosdefineStr);
					// URL_BASEを書き換え
					$iosdefineStr = str_replace('#   define URL_BASE @"/workspace/UNICORN-project/lib/FrameworkManager/sample/packages/ProjectPackage/apidocs/"', '#   define URL_BASE @"/"', $iosdefineStr);
				}
				// XXX それ以外の場合はリモートサーバの場合としている
				else if (0 === strpos($_SERVER['HTTP_HOST'], 'fwm')) {
					// ドメイン指定を書き換え
					$iosdefineStr = str_replace('#   define DOMAIN_NAME @"apitest.unicorn-project.com"', '#   define DOMAIN_NAME @"api'.substr($_SERVER['HTTP_HOST'], 3).'"', $iosdefineStr);
				}
			}
			else {
				// XXX Nginx以外の場合
				// ローカルサーバかどうか
				if (is_file('/Applications/MAMP/conf/apache/httpd.conf')){
					// おそらくMAMP-Apacheの場合
					// REQUEST_URI と $movePath からローカルのドキュメントルートPathを特定する
					$tmpPath = dirname(dirname($_SERVER['REQUEST_URI']));
					$tmpPaths = explode('/'.PROJECT_NAME.'/', $tmpPath);
					$baseURL = $tmpPaths[0].'/'.$newProjectName.'Package/apidocs/';
					$iosdefineStr = str_replace('#   define URL_BASE @"/workspace/UNICORN-project/lib/FrameworkManager/sample/packages/ProjectPackage/apidocs/"', '#   define URL_BASE @"'.$baseURL.'"', $iosdefineStr);
				}
				// Vagrant環境かどうか
				else if (is_dir('/var/www/release/.vagrant') && 0 === strpos($_SERVER['HTTP_HOST'], 'fwm')) {
					// ドメイン指定を書き換え
					$iosdefineStr = str_replace('#   define DOMAIN_NAME @"localhost"', '#   define DOMAIN_NAME @"api'.substr($_SERVER['HTTP_HOST'], 3).'"', $iosdefineStr);
					// URL_BASEを書き換え
					$iosdefineStr = str_replace('#   define URL_BASE @"/workspace/UNICORN-project/lib/FrameworkManager/sample/packages/ProjectPackage/apidocs/"', '#   define URL_BASE @"/"', $iosdefineStr);
				}
				// XXX それ以外の場合はリモートサーバの場合としている
				else if (0 === strpos($_SERVER['HTTP_HOST'], 'fwm')) {
					// ドメイン指定を書き換え
					$iosdefineStr = str_replace('#   define DOMAIN_NAME @"apitest.unicorn-project.com"', '#   define DOMAIN_NAME @"api'.substr($_SERVER['HTTP_HOST'], 3).'"', $iosdefineStr);
				}
			}
			// 新しい定義で書き換え
			file_put_contents($movePath.'/iOSProject/Project/SupportingFiles/define.h', $iosdefineStr);
			// 重いのでコマメにunset
			unset($iosdefineStr);
		}
		// XXX Android用の処理
		if (1 === (int)$argAndroidEnabled){
			@chmod($movePath.'/.android', 0666);
			$remote = false;
			// iOSサンプル内のプロジェクト内のローカルのRESTfulAPIの向け先を変える
			$androiddefineStr = file_get_contents($movePath.'/AndroidProject/Project/app/src/debug/java/com/unicorn/constant/Constant.java');
			if (FALSE !== strpos($_SERVER['HTTP_HOST'], 'localhost') && 0 === strpos($_SERVER['HTTP_HOST'], 'fwm') && FALSE !== strpos($_SERVER['SERVER_SOFTWARE'], 'nginx')){
				// おそらくNginxの場合
				//if ('443' === $_SERVER['SERVER_PORT']){
				//	// プロトコル指定を書き換え
				//	$androiddefineStr = str_replace('String PROTOCOL = "http";', 'String PROTOCOL = "https";', $androiddefineStr);
				//}
				// ローカルサーバかどうか
				if (is_file('/Applications/MAMP/conf/nginx/nginx.conf')){
					// ドメイン指定を書き換え
					$androiddefineStr = str_replace('String DOMAIN_NAME = "localhost";', 'String DOMAIN_NAME = "api'.substr($_SERVER['HTTP_HOST'], 3).'";', $androiddefineStr);
					// URL_BASEを書き換え
					$androiddefineStr = str_replace('"/workspace/UNICORN-project/lib/FrameworkManager/sample/packages/ProjectPackage/apidocs/"', '"/"', $androiddefineStr);
				}
				// Vagrant環境かどうか
				else if (is_dir('/var/www/release/.vagrant') && 0 === strpos($_SERVER['HTTP_HOST'], 'fwm')) {
					// ドメイン指定を書き換え
					$androiddefineStr = str_replace('String DOMAIN_NAME = "localhost";', 'String DOMAIN_NAME = "api'.substr($_SERVER['HTTP_HOST'], 3).'";', $androiddefineStr);
					// URL_BASEを書き換え
					$androiddefineStr = str_replace('"/workspace/UNICORN-project/lib/FrameworkManager/sample/packages/ProjectPackage/apidocs/"', '"/"', $androiddefineStr);
				}
				// XXX それ以外の場合はリモートサーバの場合としている
				else if (0 === strpos($_SERVER['HTTP_HOST'], 'fwm')) {
					$remote = true;
				}
			}
			else {
				// XXX Nginx以外の場合
				// ローカルサーバかどうか
				if (is_file('/Applications/MAMP/conf/apache/httpd.conf')){
					// おそらくMAMP-Apacheの場合
					// REQUEST_URI と $movePath からローカルのドキュメントルートPathを特定する
					$tmpPath = dirname(dirname($_SERVER['REQUEST_URI']));
					$tmpPaths = explode('/'.PROJECT_NAME.'/', $tmpPath);
					$baseURL = $tmpPaths[0].'/'.$newProjectName.'Package/apidocs/';
					$androiddefineStr = str_replace('#   define URL_BASE @"/workspace/UNICORN-project/lib/FrameworkManager/sample/packages/ProjectPackage/apidocs/"', '#   define URL_BASE @"'.$baseURL.'"', $androiddefineStr);
				}
				// Vagrant環境かどうか
				else if (is_dir('/var/www/release/.vagrant') && 0 === strpos($_SERVER['HTTP_HOST'], 'fwm')) {
					// ドメイン指定を書き換え
					$androiddefineStr = str_replace('String DOMAIN_NAME = "localhost";', 'String DOMAIN_NAME = "api'.substr($_SERVER['HTTP_HOST'], 3).'";', $androiddefineStr);
					// URL_BASEを書き換え
					$androiddefineStr = str_replace('"/workspace/UNICORN-project/lib/FrameworkManager/sample/packages/ProjectPackage/apidocs/"', '"/"', $androiddefineStr);
				}
				// XXX それ以外の場合はリモートサーバの場合としている
				else if (0 === strpos($_SERVER['HTTP_HOST'], 'fwm')) {
					$remote = true;
				}
			}
			// 新しい定義で書き換え
			if (false === $remote) {
				file_put_contents($movePath.'/AndroidProject/Project/app/src/debug/java/com/unicorn/constant/Constant.java', $androiddefineStr);
			}
			else {
				// リモートデバッグ設定の書換
				$androiddefineStr = file_get_contents($movePath.'/AndroidProject/Project/app/src/release/java/com/unicorn/constant/Constant.java');
				$androiddefineStr = str_replace('String DOMAIN_NAME = "apitest.unicorn-project.com";', 'String DOMAIN_NAME = "api'.substr($_SERVER['HTTP_HOST'], 3).'";', $androiddefineStr);
				file_put_contents($movePath.'/AndroidProject/Project/app/src/release/java/com/unicorn/constant/Constant.java', $androiddefineStr);
			}
			// 重いのでコマメにunset
			unset($androiddefineStr);
		}
		return TRUE;
	}

	public static function getProjectManageMenu($argTargetProjectName){
		$projectFWMToomMenuPath = getConfig('PROJECT_ROOT_PATH', $argTargetProjectName).'core/fwm.json';
		if (is_file($projectFWMToomMenuPath)) {
			$fwmJSON = file_get_contents($projectFWMToomMenuPath);
			$fwmConfigure = json_decode($fwmJSON, TRUE);
			return $fwmConfigure['menu'];
		}
		return NULL;
	}

	public static function getProjectManageDashboard($argTargetProjectName){
		$projectFWMToomMenuPath = getConfig('PROJECT_ROOT_PATH', $argTargetProjectName).'core/fwm.json';
		if (is_file($projectFWMToomMenuPath)) {
			$fwmJSON = file_get_contents($projectFWMToomMenuPath);
			$fwmConfigure = json_decode($fwmJSON, TRUE);
			return $fwmConfigure['Dashboard'];
		}
		return NULL;
	}

	public static function getProjectManagePermission($argTargetProjectName, $argClassName){
		$projectFWMToomMenuPath = getConfig('PROJECT_ROOT_PATH', $argTargetProjectName).'core/fwm.json';
		if (is_file($projectFWMToomMenuPath)) {
			$fwmJSON = file_get_contents($projectFWMToomMenuPath);
			$fwmConfigure = json_decode($fwmJSON, TRUE);
			$menu = $fwmConfigure['menu'];
			foreach ($menu AS $menuData) {
				$_path = explode('/', $menuData['path']);
				$_path = explode('.', $_path[count($_path)-1]);
				$dumy = array_pop($_path);
				$path = implode('.', $_path);
				if (str_replace('_', '-', strtolower(str_replace('Flow', '', $argClassName))) === $path){
					return (int)$menuData['permission'];
				}
			}
		}
		return NULL;
	}

	public static function migrateAppModel($argTargetProjectName, $argTargetPlatform){
		$DBO = DBO::sharedInstance(getConfig('DB_DSN', $argTargetProjectName));
		$tables = $DBO->getTables();
		for ($tblIdx=0; $tblIdx < count($tables); $tblIdx++){
			// テーブル毎にマイグレーション
			AppMigrationManager::generateModel($DBO, $tables[$tblIdx]['name'], $argTargetProjectName, $argTargetPlatform);
		}
		return TRUE;
	}
}

?>