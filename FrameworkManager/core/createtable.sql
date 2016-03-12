-- MySQL --
-- テーブルの構造 `fwmuser`
CREATE TABLE IF NOT EXISTS `fwmuser` (`id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'pkey', `name` varchar(1024) NOT NULL COMMENT '名前', `mail` varchar(1024) NOT NULL COMMENT 'メールアドレス', `pass` varchar(64) NOT NULL COMMENT 'パスワード(SHA256)', `permission` char(1) NOT NULL DEFAULT '9' COMMENT 'パーミッション(0:マスター〜9:スタッフ)', `default_target_project` varchar(1024) DEFAULT NULL COMMENT 'デフォルトのターゲットプロジェクト名', PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='ユーザーテーブル';
-- テーブルの構造 `session`
CREATE TABLE IF NOT EXISTS `session` (`token` varchar(255) NOT NULL COMMENT 'ワンタイムトークン', `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'トークン作成日時', PRIMARY KEY (`token`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='セッションテーブル';
-- テーブルの構造 `sessiondata`
CREATE TABLE IF NOT EXISTS `sessiondata` (`identifier` varchar(96) NOT NULL COMMENT 'deviceテーブルのPkey', `data` text COMMENT 'jsonシリアライズされたセッションデータ', `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '変更日時', PRIMARY KEY (`identifier`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='セッションデータテーブル';
-- Default user record
INSERT INTO `fwmuser` (`id`, `name`, `mail`, `pass`, `permission`) SELECT '1', 'SUPER USER', 'root@super.user', '9d13814473e7d0316260089f089be6e723aecf883be151a48592952d6ac1d98d', '0' FROM DUAL WHERE NOT EXISTS(SELECT `id` FROM `fwmuser` WHERE `id` = '1');
