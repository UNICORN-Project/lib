//
//  UserModel.h
//  自由に拡張可能です
//
//  Copyright (c) 2014年 saimushi. All rights reserved.
//
// XXX 利用する場合の注意事項
// modelのヘッダーファイルを、他のクラスのヘッダーでimportしないで下さい！
// 循環参照エラーになる場合があります！
// ヘッダーでmodelを利用する場合は、@class指定を使って下さい！

#import "UserModelBase.h"

@class ProfileModel;

@interface UserModel : UserModelBase
{
    ProfileModel *profile;
}

@property (strong, nonatomic) ProfileModel *profile;


@end
