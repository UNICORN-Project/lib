<?php

class AppMigrationManager {
	public static function generateModel($argDBO, $argTblName, $argTargetProjectName = NULL, $argTargetPlatform = NULL) {
		$iosEnable = FALSE;
		$androidEnable = FALSE;
		if(function_exists('getConfig') && FALSE !== is_file(getConfig('PROJECT_ROOT_PATH').'.ios')){
			$iosEnable = TRUE;
		}
		if(function_exists('getConfig') && FALSE !== is_file(getConfig('PROJECT_ROOT_PATH').'.android')){
			$androidEnable = TRUE;
		}
		if (FALSE === $iosEnable && FALSE === $androidEnable){
			// マイグレーション対象無し
			return;
		}
		$tableName = strtolower ( $argTblName );
		$modelName = str_replace ( ' ', '', ucwords ( str_replace ( '_', ' ', $tableName ) ) );
		$describes = $argDBO->getTableDescribes ( $argTblName );
		if (is_array ( $describes ) && count ( $describes ) > 0) {
			debug('AppModel '.$tableName.' '.$argTargetProjectName);
			if (TRUE === $iosEnable && NULL === $argTargetPlatform || 'iOS' === $argTargetPlatform) {
				$headerfile = file_get_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/core/EmptyModelBase.h' );
				$modelfile = file_get_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/core/EmptyModelBase.m' );
				$class = '';
				$import = '';
				$protected = '';
				$public = '';
				$synthesize = '';
				$flags = '';
				$accesser = '';
				$init = '';
				$save = '';
				$convert = '';
				$set = '';
				$reset = '';
				foreach ( $describes as $colName => $describe ) {
					if ('id' !== $colName) {
						$protected .= '    NSString *' . $colName . ';' . PHP_CR . PHP_LF;
						$public .= '@property (strong, nonatomic) NSString *' . $colName . ';' . PHP_CR . PHP_LF;
						$synthesize .= '@synthesize ' . $colName . ';' . PHP_CR . PHP_LF;
						$flags .= '    BOOL ' . $colName . '_replaced;' . PHP_CR . PHP_LF;
						$accesser .= '-(void)set' . ucfirst ( $colName ) . ':(NSString *)arg' . ucfirst ( $colName ) . PHP_CR . PHP_LF;
						$accesser .= '{' . PHP_CR . PHP_LF;
						$accesser .= '    ' . $colName . ' = arg' . ucfirst ( $colName ) . ';' . PHP_CR . PHP_LF;
						$accesser .= '    ' . $colName . '_replaced = YES;' . PHP_CR . PHP_LF;
						$accesser .= '    replaced = YES;' . PHP_CR . PHP_LF;
						$accesser .= '}' . PHP_CR . PHP_LF . PHP_CR . PHP_LF;
						$init .= '        ' . $colName . '_replaced = NO;' . PHP_CR . PHP_LF;
						$save .= '        if(YES == ' . $colName . '_replaced){' . PHP_CR . PHP_LF;
						$save .= '            [saveParams setValue:self.' . $colName . ' forKey:@"' . $colName . '"];' . PHP_CR . PHP_LF;
						$save .= '        }' . PHP_CR . PHP_LF;
						$convert .= '    [newDic setObject:self.' . $colName . ' forKey:@"' . $colName . '"];' . PHP_CR . PHP_LF;
						$set .= '    self.' . $colName . ' = [argDataDic objectForKey:@"' . $colName . '"];' . PHP_CR . PHP_LF;
						$reset .= '    ' . $colName . '_replaced = NO;' . PHP_CR . PHP_LF;
						// DEEP REST
						debug('AppModel isDeep?'.strpos($colName, '_id'));
						if (1 < strpos($colName, '_id') && 3 === strlen(substr($colName, strpos($colName, '_id'))) && getConfig ( 'REST_RESOURCE_OWNER_PKEY_NAME', $argTargetProjectName ) !== $colName){
							$subTableNameLower = strtolower(substr($colName, 0, strlen($colName) -3));
							$subTableName = ucfirst($subTableNameLower);
							$class .= '@class ' . $subTableName . 'Model;' . PHP_CR . PHP_LF;
							$import .= '#import "' . $subTableName . 'Model.h"' . PHP_CR . PHP_LF;
							$protected .= '    /* ' . $subTableName . 'モデルのDEEP-RESTモデル */' . PHP_CR . PHP_LF;
							$protected .= '    ' . $subTableName . 'Model *' . $subTableNameLower . ';' . PHP_CR . PHP_LF;
							$public .= '@property (strong, nonatomic) ' . $subTableName . 'Model *' . $subTableNameLower . ';' . PHP_CR . PHP_LF;
							$synthesize .= '@synthesize ' . $subTableNameLower . ';' . PHP_CR . PHP_LF;
							$convert .= '    /* ' . $subTableName . 'モデルのDEEP-REST */' . PHP_CR . PHP_LF;
							$convert .= '    NSMutableArray *' . $subTableNameLower . 'List = [[NSMutableArray alloc] init];' . PHP_CR . PHP_LF;
							$convert .= '    if(0 < self.' . $subTableNameLower . '.total){' . PHP_CR . PHP_LF;
							$convert .= '        do {' . PHP_CR . PHP_LF;
							$convert .= '            [' . $subTableNameLower . 'List addObject:[self.' . $subTableNameLower . ' convertModelData]];' . PHP_CR . PHP_LF;
							$convert .= '        } while (YES == [self.' . $subTableNameLower . ' next]);' . PHP_CR . PHP_LF;
							$convert .= '    }' . PHP_CR . PHP_LF;
							$convert .= '    [newDic setObject:' . $subTableNameLower . 'List forKey:@"' . $subTableNameLower . '"];' . PHP_CR . PHP_LF;
							$set .= '    /* ' . $subTableName . 'モデルのDEEP-REST */' . PHP_CR . PHP_LF;
							$set .= '    NSMutableArray *' . $subTableNameLower . 'Dic = [argDataDic objectForKey:@"' . $subTableNameLower . '"];' . PHP_CR . PHP_LF;
							$set .= '    if(nil != ' . $subTableNameLower . 'Dic){' . PHP_CR . PHP_LF;
							$set .= '        self.' . $subTableNameLower . ' = [[' . $subTableName . 'Model alloc] init:protocol :domain :urlbase :tokenKeyName :cryptKey :cryptIV :timeout];' . PHP_CR . PHP_LF;
							$set .= '        [self.' . $subTableNameLower . ' setModelData:[argDataDic objectForKey:@"' . $subTableNameLower . '"]];' . PHP_CR . PHP_LF;
							$set .= '    }' . PHP_CR . PHP_LF;
						}
					}
					else {
						$convert .= '    [newDic setObject:self.ID forKey:@"id"];' . PHP_CR . PHP_LF;
						$set .= '    self.ID = [argDataDic objectForKey:@"id"];' . PHP_CR . PHP_LF;
					}
				}
				$headerfile = str_replace ( '%modelName%', $modelName, $headerfile );
				$headerfile = str_replace ( '%class%', $class, $headerfile );
				$headerfile = str_replace ( '%protected%', $protected, $headerfile );
				$headerfile = str_replace ( '%public%', $public, $headerfile );
				$modelfile = str_replace ( '%import%', $import, $modelfile );
				$modelfile = str_replace ( '%modelName%', $modelName, $modelfile );
				$modelfile = str_replace ( '%tableName%', $tableName, $modelfile );
				$modelfile = str_replace ( '%flags%', $flags, $modelfile );
				$modelfile = str_replace ( '%synthesize%', $synthesize, $modelfile );
				$modelfile = str_replace ( '%accesser%', $accesser, $modelfile );
				$modelfile = str_replace ( '%init%', $init, $modelfile );
				$modelfile = str_replace ( '%save%', $save, $modelfile );
				$modelfile = str_replace ( '%convert%', $convert, $modelfile );
				$modelfile = str_replace ( '%set%', $set, $modelfile );
				$modelfile = str_replace ( '%reset%', $reset, $modelfile );
				file_put_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/iOSProject/Project/Class/Model/' . $modelName . 'ModelBase.h', $headerfile );
				file_put_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/iOSProject/Project/Class/Model/' . $modelName . 'ModelBase.m', $modelfile );
				if (! is_file ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/iOSProject/Project/Class/Model/' . $modelName . 'Model.m' )) {
					// まだ該当のモデルの最下層ファイルがなければ生成する
					$headerfile = file_get_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/core/EmptyModel.h' );
					$modelfile = file_get_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/core/EmptyModel.m' );
					$headerfile = str_replace ( '%modelName%', $modelName, $headerfile );
					$modelfile = str_replace ( '%modelName%', $modelName, $modelfile );
					file_put_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/iOSProject/Project/Class/Model/' . $modelName . 'Model.h', $headerfile );
					file_put_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/iOSProject/Project/Class/Model/' . $modelName . 'Model.m', $modelfile );
				}
			}
			if (TRUE === $androidEnable && NULL === $argTargetPlatform || 'android' === $argTargetPlatform) {
				$modelfile = file_get_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetPlatform ) . '/core/EmptyModelBase.java' );
				$public = '';
				$flags = '';
				$accesser = '';
				$save = '';
				$convert = '';
				$set = '';
				$reset = '';
				foreach ( $describes as $colName => $describe ) {
					if ('id' !== $colName) {
						$public .= PHP_TAB . 'public String ' . $colName . ';' . PHP_CR . PHP_LF;
						$flags .= PHP_TAB . 'public boolean ' . $colName . '_replaced;' . PHP_CR . PHP_LF;
						$accesser .= PHP_TAB . '/**' . PHP_CR . PHP_LF;
						$accesser .= PHP_TAB . ' * setterメソッドです' . PHP_CR . PHP_LF;
						$accesser .= PHP_TAB . ' * setterによりフィールドが変更されたことを保持するreplacedフラグをtrueに書き換え' . PHP_CR . PHP_LF;
						$accesser .= PHP_TAB . ' * どのフィールドが変更されたかを保持するフィールド名_replacedフラグをtrueに書き換えます' . PHP_CR . PHP_LF;
						$accesser .= PHP_TAB . ' * @param arg' . ucfirst ( $colName ) . ' ' . $colName . 'が入っています' . PHP_CR . PHP_LF;
						$accesser .= PHP_TAB . ' */' . PHP_CR . PHP_LF;
						$accesser .= PHP_TAB . 'public void set' . ucfirst ( $colName ) . '(String arg' . ucfirst ( $colName ) . ') {' . PHP_CR . PHP_LF;
						$accesser .= PHP_TAB . PHP_TAB . $colName . ' = arg' . ucfirst ( $colName ) . ';' . PHP_CR . PHP_LF;
						$accesser .= PHP_TAB . PHP_TAB . $colName . '_replaced = true;' . PHP_CR . PHP_LF;
						$accesser .= PHP_TAB . PHP_TAB . 'replaced = true;' . PHP_CR . PHP_LF;
						$accesser .= PHP_TAB . '}' . PHP_CR . PHP_LF . PHP_CR . PHP_LF;
						$save .= PHP_TAB . PHP_TAB . PHP_TAB . 'if (' . $colName . '_replaced) {' . PHP_CR . PHP_LF;
						$save .= PHP_TAB . PHP_TAB . PHP_TAB . PHP_TAB . 'argSaveParams.put("' . $colName . '", ' . $colName . ');' . PHP_CR . PHP_LF;
						$save .= PHP_TAB . PHP_TAB . PHP_TAB . '}' . PHP_CR . PHP_LF;
						$convert .= PHP_TAB . PHP_TAB . 'newMap.put("' . $colName . '", ' . $colName . ');' . PHP_CR . PHP_LF;
						$set .= PHP_TAB . PHP_TAB . $colName . ' = (String) map.get("' . $colName . '");' . PHP_CR . PHP_LF;
						$reset .= PHP_TAB . PHP_TAB . $colName . '_replaced = false;' . PHP_CR . PHP_LF;
					}
					else {
						$convert .= PHP_TAB . PHP_TAB . 'newMap.put("id", ID);';
						$set .= PHP_TAB . PHP_TAB . 'ID = (String) map.get("id");';
					}
				}
				$modelfile = str_replace ( '%modelName%', $modelName, $modelfile );
				$modelfile = str_replace ( '%tableName%', $tableName, $modelfile );
				$modelfile = str_replace ( '%public%', $public, $modelfile );
				$modelfile = str_replace ( '%flags%', $flags, $modelfile );
				$modelfile = str_replace ( '%accesser%', $accesser, $modelfile );
				$modelfile = str_replace ( '%save%', $save, $modelfile );
				$modelfile = str_replace ( '%convert%', $convert, $modelfile );
				$modelfile = str_replace ( '%set%', $set, $modelfile );
				$modelfile = str_replace ( '%reset%', $reset, $modelfile );
				file_put_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/AndroidProject/Project/app/src/main/java/jp/co/project/model/base/' . $modelName . 'ModelBase.java', $modelfile );
				if (! is_file ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/AndroidProject/Project/app/src/main/java/jp/co/project/model/' . $modelName . 'Model.java' )) {
					// まだ該当のモデルの最下層ファイルがなければ生成する
					$modelfile = file_get_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetPlatform ) . '/core/EmptyModel.java' );
					$modelfile = str_replace ( '%modelName%', $modelName, $modelfile );
					file_put_contents ( getConfig ( 'PROJECT_ROOT_PATH', $argTargetProjectName ) . '/AndroidProject/Project/app/src/main/java/jp/co/project/model/' . $modelName . 'Model.java', $modelfile );
				}
			}
			if (NULL === $argTargetPlatform || 'cocos' === $argTargetPlatform) {
			}
			if (NULL === $argTargetPlatform || 'swift' === $argTargetPlatform) {
			}
			if (NULL === $argTargetPlatform || 'cocosjs' === $argTargetPlatform) {
			}
		}
		return TRUE;
	}
}

?>