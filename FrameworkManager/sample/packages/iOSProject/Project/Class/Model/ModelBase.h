//
//  ModelBase.h
//
//  Created by saimushi on 2014/06/17.
//  Copyright (c) 2014年 saimushi. All rights reserved.
//


// モデル参照モード
typedef enum{
    IDResource,
    myIDResource,
    listedResource,
    myListedResource,
} loadResourceMode;

// 通信パケット監視用クラス
@interface ProgressAgent : NSObject
{
    double packetSentBytes;
    double totalSentBytes;
    double totalBytes;
}
@property (nonatomic) double packetSentBytes;
@property (nonatomic) double totalSentBytes;
@property (nonatomic) double totalBytes;
@end


#import "Request.h"
#import "SBJsonAgent.h"

@protocol ModelDelegate;

@interface ModelBase : NSObject <RequestDelegate>
{
    // モデルの共通規定値
    NSString *protocol;
    NSString *domain;
    NSString *urlbase;
    NSString *cryptKey;
    NSString *cryptIV;
    int timeout;
    NSString *tokenKeyName;
    NSString *deviceTokenKeyName;
    NSString *modelName;
    NSString *ID;
    // 自分のリソースを参照する場合の修飾子（デフォルトでは必ず自分のリソース参照となるので、loadやsaveの際に空文字を入れて下さい）
    NSString *myResourcePrefix;
    // リクエストメソッド
    NSString *requestMethod;
    // モデルは原則配列を許容する
    int index;
    // ページング上のレコード総数
    int total;
    // テーブル上の該当レコード総件数
    int records;
    // XXX LIMIT OFFSETを自動化したく無い場合、-1を指定して下さい
    int limit;
    int offset;
    NSMutableArray *list;
    // 通信に関する変数
    BOOL replaced;
    BOOL requested;
    // XXX DEEP RESTを利用しない場合はNOを指定して下さい
    BOOL isDeep;
    NSMutableDictionary *response;
    int statusCode;
    // Blockでハンドラを受け取るバージョンの為に用意
    RequestCompletionHandler completionHandler;
    // 非同期用にデレゲートを用意
	id <ModelDelegate> delegate;
    NSURLSessionTask* sessionDataTask;
}

@property (strong, nonatomic) NSURLSessionTask *sessionDataTask;
@property (strong, nonatomic) NSString *modelName;
@property (strong, nonatomic) NSString *ID;
@property (nonatomic) int index;
@property (nonatomic) int total;
@property (nonatomic) int records;
@property (nonatomic) int limit;
@property (nonatomic) int offset;
@property (nonatomic) BOOL isDeep;
@property (strong, nonatomic) id<ModelDelegate> delegate;

/* シングルトンでModelクラスを受け取る */
+ (id)getInstance;

/* 各種モデルの初期化処理 */
- (id)init:(NSString *)argProtocol :(NSString *)argDomain :(NSString *)argURLBase :(NSString *)argTokenKeyName;
- (id)init:(NSString *)argProtocol :(NSString *)argDomain :(NSString *)argURLBase :(NSString *)argTokenKeyName :(int)argTimeout;
/* トークンの生成時に暗号化を使う場合は以下のメソッドでinitする必要があります */
/* XXX また、暗号化鍵を渡さないでinitする場合は、- (NSString *)createToken; をオーバーライドして下さい！ */
- (id)init:(NSString *)argProtocol :(NSString *)argDomain :(NSString *)argURLBase :(NSString *)argTokenKeyName :(NSString *)argCryptKey :(NSString *)argCryptIV;
- (id)init:(NSString *)argProtocol :(NSString *)argDomain :(NSString *)argURLBase :(NSString *)argTokenKeyName :(NSString *)argCryptKey :(NSString *)argCryptIV :(int)argTimeout;
- (id)init:(NSString *)argProtocol :(NSString *)argDomain :(NSString *)argURLBase :(NSString *)argTokenKeyName :(NSString *)argCryptKey :(NSString *)argCryptIV :(NSString *)argDeviceTokenKeyName :(int)argTimeout;

/* モデルのデータを外部からJSON配列のまま貰ってModelの最初期化を行えるようにするモデルデータのアクセサ */
- (void)setModelData:(NSMutableArray *)argDataArray;
- (void)setModelData:(NSMutableArray *)argDataArray :(int)argIndex;

// RESTfulURLの生成ロジック
// XXX システムによって実装が変わる場合はオーバーライドして適宜変更して下さい
- (NSString *)createURLString:(NSString *)argProtocol :(NSString *)argDomain :(NSString *)argURLBase :(NSString *)argMyResourcePrefix :(NSString *)argModelName :(NSString *)argResourceID;

/* 単一モデルを読み込む(読み込み処理をモデル側で実装を変えたい場合はこのメソッドをオーバーライドする) */
- (BOOL)load:(loadResourceMode)argLoadResourceMode;
/* 単一モデルを読み込む(BlockでHandlerを受け取れるバージョン:読み込み処理をモデル側で実装を変えた場合はこのメソッドをオーバーライドする) */
- (BOOL)load:(loadResourceMode)argLoadResourceMode :(RequestCompletionHandler)argCompletionHandler;
/* 条件指定でモデルを読み込む(読み込み処理をモデル側で実装を変えた場合はこのメソッドをオーバーライドする) */
- (BOOL)query:(NSMutableDictionary *)argWhereParams :(loadResourceMode)argLoadResourceMode;
/* 条件指定でモデルを読み込む(BlockでHandlerを受け取れるバージョン:読み込み処理をモデル側で実装を変えた場合はこのメソッドをオーバーライドする) */
- (BOOL)query:(NSMutableDictionary *)argWhereParams :(loadResourceMode)argLoadResourceMode :(RequestCompletionHandler)argCompletionHandler;

/* モデルを読み込む(Protected:参照処理の実態) */
- (BOOL)_load:(int)argListed :(NSMutableDictionary *)argParams;
/* モデルを保存する(モデルが継承してオーバーライドする空のメソッド定義) */
- (BOOL)save;
/* モデルを保存する(BlockでHandlerを受け取れるバージョン:読み込み処理をモデル側で実装を変えた場合はこのメソッドをオーバーライドする) */
- (BOOL)save:(RequestCompletionHandler)argCompletionHandler;
/* モデルを保存する(Protected:保存処理の実態) */
- (BOOL)_save:(NSMutableDictionary *)argSaveParams;
/* モデルを保存する(Protected:ファイル添付付き) */
/* XXX 大きいファイルのアップロードには- (BOOL)save:(NSMutableDictionary *)argSaveParams :(NSURL *)argUploadFilePath;を使って下さい！ */
- (BOOL)_save:(NSMutableDictionary *)argSaveParams :(NSData *)argUploadData :(NSString *)argUploadDataName :(NSString *)argUploadDataContentType :(NSString *)argUploadDataKey;
/* ファイルを一つのモデルリソースと見立てて保存する(Protected:ファイルアップロード) */
/* PUTメソッドでのアップロード処理を強制します！ POSTを利用したい場合は、小クラスで、メセッドの時実行前に「requestMethod」に「POST」を指定して下さい！ */
- (BOOL)_save:(NSMutableDictionary *)argSaveParams :(NSURL *)argUploadFilePath;

/* 特殊なメソッド1 インクリメント(加算:モデルが継承してオーバーライドする空のメソッド定義) */
- (BOOL)increment;
- (BOOL)_increment:(NSMutableDictionary *)argSaveParams;
/* 特殊なメソッド2 デクリメント(減算:モデルが継承してオーバーライドする空のメソッド定義) */
- (BOOL)decrement;
- (BOOL)_decrement:(NSMutableDictionary *)argSaveParams;

///* 端末固有IDの保存 */
//+ (void)saveIdentifier:(NSString *)argIdentifier :(NSString *)argCryptKey :(NSString *)argCryptIV;
///* 端末固有IDの読み込み */
//+ (NSString *)loadIdentifier:(NSString *)argCryptKey :(NSString *)argCryptIV;
//
///* レコード所有者IDの保存 */
//+ (void)saveOwnerID:(NSString *)argIdentifier :(NSString *)argCryptKey :(NSString *)argCryptIV;
///* レコード所有者IDの読み込み */
//+ (NSString *)loadOwnerID:(NSString *)argCryptKey :(NSString *)argCryptIV;
//
///* レコード所有者IDの保存 */
//+ (void)saveOwnerName:(NSString *)argIdentifier :(NSString *)argCryptKey :(NSString *)argCryptIV;
///* レコード所有者IDの読み込み */
//+ (NSString *)loadOwnerName:(NSString *)argCryptKey :(NSString *)argCryptIV;
//
///* レコード所有者画像URLの保存 */
//+ (void)saveOwnerImageURL:(NSString *)argIdentifier :(NSString *)argCryptKey :(NSString *)argCryptIV;
///* レコード所有者画像URLの読み込み */
//+ (NSString *)loadOwnerImageURL:(NSString *)argCryptKey :(NSString *)argCryptIV;

/* デバイストークンの保存 */
+ (void)saveDeviceTokenString:(NSString *)argDeviceTokenString;
+ (void)saveDeviceTokenData:(NSData *)argDeviceTokenData;
/* デバイストークンの読み込み */
+ (NSString *)loadDeviceToken;

// モデルの配列操作に関する処理
- (BOOL)next;
- (id)objectAtIndex:(int)argIndex;
- (void)insertObject:(ModelBase *)argModel :(int)argIndex;
- (void)replaceObject:(ModelBase *)argModel :(int)argIndex;
- (void)addObject:(ModelBase *)argModel;
- (void)removeObjectAtIndex:(int)argIndex;

/* モデルの実態側でメソッドを実装して下さい！ */
- (void)resetReplaceFlagment;
- (NSMutableDictionary *)convertModelData;
/* モデルデータをセットする(モデルが継承してオーバーライドする空のメソッド定義) */
- (void)_setModelData:(NSMutableDictionary *)argDataDic;
/* 該当のデータを持つModelのIndexの一覧を返す */
- (NSIndexSet *)search:(NSString *)argSearchKey :(NSString *)argSearchValue;

// ステータスコードに応じたRESTfulエラーメッセージを表示
+(void)showRequestError:(int)argStatusCode;

@end

/* Modelの非同期通信用デレゲート */
/* Modelを非同期で使いたい場合だけ、Delegateに指定して下さい */
@protocol ModelDelegate  <NSObject>

@optional
- (void)didReceiveValidateError:(NSString *)argValidateErrorMsg;
- (void)didReceiveMustUpdate:(NSString *)argUpdateURL;
- (void)didReceiveAppBadgeNum:(NSString *)argBadgeNumStr;
- (void)didReceiveNotifyMessage:(NSString *)argNotifyMessage;
- (void)didFinishSuccess:(ModelBase*)model :(NSHTTPURLResponse *)responseHeader :(NSString *)responseBody;
- (void)didFinishError:(ModelBase*)model :(NSHTTPURLResponse *)responseHeader :(NSString *)responseBody :(NSError *)failedHandler;
- (void)didChangeProgress:(ModelBase*)model :(ProgressAgent *)progressAgent;

@end
