//
//  TimelineViewController.m
//
//  Created by saimushi on 2014/09/19.
//  Copyright (c) 2014年 saimushi. All rights reserved.
//

#import "TimelineViewController.h"
#import "TimelineModel.h"
#import "MTextFieldViewController.h"

@interface TimelineViewController ()
{
    // Private
}
@end

@implementation TimelineViewController


#pragma mark Custom Methods

- (void)loadMyProfile
{
    ProfileModel *myProfile = [[ProfileModel alloc] init];
    [myProfile load:myIDResource :^(BOOL success, NSInteger statusCode, NSHTTPURLResponse *responseHeader, NSString *responseBody, NSError *error) {
        if (success){
            APPDELEGATE.me.profile = myProfile;
            APPDELEGATE.me.profile.ID = APPDELEGATE.me.ID;
        }
    }];
}


#pragma mark Over ride Custom Methods

- (UITableViewCell *)generateDataCell:(UITableViewCell *)argCell :(TimelineModel *)argModel;
{
    // 一言
    ((UILabel *)[argCell.contentView viewWithTag:1]).text = argModel.text;
    // 日時
    ((UILabel *)[argCell.contentView viewWithTag:2]).text = argModel.modified;
    // 投稿者名
    NSString *nameLabelStr = NSLocalizedString(@"@名無し", @"@名無し");
    if (nil != argModel.profile.name && 0 < [argModel.profile.name length]){
        nameLabelStr = [NSString stringWithFormat:@"@%@", argModel.profile.name];
    }
    ((UILabel *)[argCell.contentView viewWithTag:3]).text = nameLabelStr;
    // 投稿者画像
    UIImageView *profileImageView = ((UIImageView *)[argCell.contentView viewWithTag:4]);
    if (nil != argModel.profile.image && ![argModel.profile.image isEqualToString:@""]){
        [profileImageView hnk_setImage:[UIImage imageWithData:[[NSData alloc] initWithBase64EncodedString:argModel.profile.image
                                                                                           options:NSDataBase64DecodingIgnoreUnknownCharacters]] withKey:[argModel.profile.image sha1HexHash]  success:^(UIImage *image) {
            profileImageView.image = image;
        } failure:^(NSError *error) {
            //
        }];
    }
    else {
        profileImageView.image = [UIImage imageNamed:@"NoImageProfile"];
    }
    return argCell;
}

- (void)dataListLoad
{
    // デバイストークン取得
    [APPDELEGATE registerDeviceToken];
    [super dataListLoad];
}

- (void)addDataAndReloadView:(TimelineModel *)argModel;
{
    // データをローカルで追加して再描画
    // Viewの再描画
    if (nil != self.data && [searchText isEqualToString:@""]){
        [self.data insertObject:argModel :0];
        [self.dataListView reloadData];
    }
}


#pragma mark Data Search Methods

- (NSString *)generateSearchQuery:(NSString *)argSearchText;
{
    return [NSString stringWithFormat:@"text LIKE '%@%@%@'", @"%", argSearchText, @"%"];
}


#pragma mark UIView Controller Event Methods

- (id)init
{
    self = [super init];
    if(self != nil){
        dataCellIdentifier = @"timelineTableCell";
        nodataCellIdentifier = @"NodataCellView";
        showDetailSegueIdentifier = nil;
        resourceMode = listedResource;
        // モデルクラス初期化
        self.data = [[TimelineModel alloc] init];
    }
    return self;
}

- (id)initWithCoder:(NSCoder*)aDecoder
{
    self = [super initWithCoder:aDecoder];
    if(self != nil){
        dataCellIdentifier = @"timelineTableCell";
        nodataCellIdentifier = @"NodataCellView";
        showDetailSegueIdentifier = nil;
        resourceMode = listedResource;
        // モデルクラス初期化
        self.data = [[TimelineModel alloc] init];
    }
    return self;
}

- (void)viewDidLoad
{
    // 初期化
    [super viewDidLoad];

    // ユーザー情報の読み込み
    if (nil == APPDELEGATE.me){
        APPDELEGATE.me = [[UserModel alloc] init];
        [APPDELEGATE.me load:myIDResource :^(BOOL success, NSInteger statusCode, NSHTTPURLResponse *responseHeader, NSString *responseBody, NSError *error) {
            if (success){
                // リストデータ読み込み
                [self dataListLoad];
                [self performSelectorInBackground:@selector(loadMyProfile) withObject:nil];
            }
        }];
    }
    else {
        // リストデータ読み込み
        [self dataListLoad];
    }
}


#pragma mark IBAction Methods

- (IBAction)add:(id)sender
{
    [MTextFieldViewController show:NO :self :NSLocalizedString(@"Add", @"追加") :@"" :1 :1 :128 :NO :UIKeyboardTypeDefault :^(NSString *argInputText, MTextFieldViewController *argVC) {
        TimelineModel *newRecode = [[TimelineModel alloc] init];
        newRecode.text = argInputText;
        newRecode.profile_id = APPDELEGATE.me.ID;
        [self startDataLoad];
        [newRecode save:^(BOOL success, NSInteger statusCode, NSHTTPURLResponse *responseHeader, NSString *responseBody, NSError *error) {
            [self endDataLoad:success :statusCode :responseHeader :responseBody :error];
            // MTextFieldViewControllerを終了
            [argVC onPushBackButton:nil];
            // データのローカル追加と再描画
            newRecode.profile = APPDELEGATE.me.profile;
            TimelineViewController *tlVC;
            tlVC = [((UINavigationController *)[((UITabBarController *)APPDELEGATE.window.rootViewController).viewControllers objectAtIndex:0]).viewControllers objectAtIndex:0];
            if ([tlVC respondsToSelector:@selector(addDataAndReloadView:)]){
                [tlVC addDataAndReloadView:newRecode];
            }
            // 隣のタブも表示更新
            tlVC = [((UINavigationController *)[((UITabBarController *)APPDELEGATE.window.rootViewController).viewControllers objectAtIndex:1]).viewControllers objectAtIndex:0];
            if ([tlVC respondsToSelector:@selector(addDataAndReloadView:)]){
                [tlVC addDataAndReloadView:newRecode];
            }
        }];
    }];
}

@end
