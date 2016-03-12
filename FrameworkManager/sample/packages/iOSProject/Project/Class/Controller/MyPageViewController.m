//
//  MyPageViewController.m
//
//  Created by saimushi on 2015/07/14.
//  Copyright (c) 2015 saimushi. All rights reserved.
//

#import <AVFoundation/AVFoundation.h>
#import "MyPageViewController.h"
#import "MTextFieldViewController.h"
#import "MActionsheetButtonView.h"
#import "MCropImageView.h"
#import "TimelineModel.h"

@interface MyPageViewController () <UIImagePickerControllerDelegate>
{
    // Private
    __weak IBOutlet UIImageView *imageView;
    __weak IBOutlet UILabel *nameLabel;
}
@end

@implementation MyPageViewController


#pragma mark Custom Methods

- (void)showData
{
    // プロフィール画像の初期化
    if (nil != APPDELEGATE.me.profile.image && ![APPDELEGATE.me.profile.image isEqualToString:@""]){
        [imageView hnk_setImage:[UIImage imageWithData:[[NSData alloc] initWithBase64EncodedString:APPDELEGATE.me.profile.image
                                                                                           options:NSDataBase64DecodingIgnoreUnknownCharacters]] withKey:[APPDELEGATE.me.profile.image sha1HexHash]  success:^(UIImage *image) {
            imageView.image = image;
        } failure:^(NSError *error) {
            //
        }];
    }
    // 名前の初期化
    nameLabel.text = APPDELEGATE.me.profile.name;
}

- (void)updateData
{
    // 表示中のタイムライン上の自分のプロフィールを全て新しいデータで置き換える
    TimelineViewController *tlVC = [((UINavigationController *)[((UITabBarController *)APPDELEGATE.window.rootViewController).viewControllers objectAtIndex:0]).viewControllers objectAtIndex:0];
    NSIndexSet *hitIndexs = [tlVC.data search:@"profile_id" :APPDELEGATE.me.profile.ID];
    if (nil != hitIndexs && 0 < [hitIndexs count]){
        NSUInteger index = [hitIndexs firstIndex];
        while(index != NSNotFound) {
            NSLog(@"index = %d", (int)index);
            TimelineModel *rowData = [tlVC.data objectAtIndex:(int)index];
            rowData.profile = APPDELEGATE.me.profile;
            [tlVC.data replaceObject:rowData :(int)index];
            // 次のIndexへ
            index = [hitIndexs indexGreaterThanIndex:index];
        }
        [tlVC.dataListView reloadData];
    }
}

#pragma mark UIView Controller Event Methods

- (void)viewDidLoad
{
    // 初期化
    [super viewDidLoad];
    [self showData];
}

#pragma mark TableView Delegate

- (void)tableView:(UITableView*)tableView didSelectRowAtIndexPath:(NSIndexPath *)indexPath
{
    // 選択解除
    [tableView deselectRowAtIndexPath:indexPath animated:NO];
    if (0 == indexPath.section && 0 == indexPath.row){
        UIImagePickerController *ipc = [[UIImagePickerController alloc] init];
        ipc.delegate = self;
        ipc.allowsEditing = NO;
        ipc.sourceType = UIImagePickerControllerSourceTypePhotoLibrary;
        if([UIImagePickerController isSourceTypeAvailable:UIImagePickerControllerSourceTypeCamera]){
            // アクションシート表示
            [MActionsheetButtonView showActionsheetButtonView:[NSArray arrayWithObjects:NSLocalizedString(@"カメラで画像を撮影する", @"カメラで画像を撮影する"), NSLocalizedString(@"既にある画像を使う", @"カメラで画像を撮影する"), nil]
                                               isCancelButton:YES
                                                   completion:^(NSInteger buttonIndex) {
                                                       // １番上のボタンを押した時
                                                       if (buttonIndex == 1) {
                                                           NSLog(@"first button pushed");
                                                           AVAuthorizationStatus status = [AVCaptureDevice authorizationStatusForMediaType:AVMediaTypeVideo];
                                                           if((status == AVAuthorizationStatusRestricted) || (status == AVAuthorizationStatusDenied)) {
                                                               if([[[UIDevice currentDevice] systemVersion] compare:@"8.0" options:NSNumericSearch] == NSOrderedAscending){
                                                                   // i0S7以前の処理
                                                                   [CustomAlert alertShow:@"" message:@"カメラへのアクセスが未許可です。\n設定でカメラの使用を許可してください。"];
                                                               }
                                                               else {
                                                                   // iOS8の処理
                                                                   [CustomAlert alertShow:@"" message:@"カメラへのアクセスが未許可です。\n設定でカメラの使用を許可してください。" buttonLeft:@"キャンセル" buttonRight:@"OK" completionHandler:^(BOOL result) {
                                                                       if(result){
                                                                           // OKを押したら設定画面に遷移
                                                                           [[UIApplication sharedApplication] openURL:[NSURL URLWithString:UIApplicationOpenSettingsURLString]];
                                                                           return;
                                                                       }
                                                                   }];
                                                               }
                                                               return;
                                                           }
                                                           ipc.sourceType = UIImagePickerControllerSourceTypeCamera;
                                                       }
                                                       // ２番めのボタンを押した時
                                                       else if (buttonIndex == 2) {
                                                           ipc.sourceType = UIImagePickerControllerSourceTypePhotoLibrary;
                                                           NSLog(@"second button pushed");
                                                       }
                                                       [self presentViewController:ipc animated:YES completion:nil];
                                                   }];
        }
        else {
            // 無条件でカメラロール表示
            [self presentViewController:ipc animated:YES completion:nil];
        }
    }
    else if (1 == indexPath.section && 0 == indexPath.row){
        // 名前の保存処理
        [MTextFieldViewController show:NO :self :NSLocalizedString(@"名前の編集", @"名前の編集") :nameLabel.text :1 :1 :10 :NO :UIKeyboardTypeDefault :^(NSString *argInputText, MTextFieldViewController *argVC) {
            if (![argInputText isEqualToString:nameLabel.text]){
                [self startDataLoad];
                APPDELEGATE.me.profile.name = argInputText;
                [APPDELEGATE.me.profile save:^(BOOL success, NSInteger statusCode, NSHTTPURLResponse *responseHeader, NSString *responseBody, NSError *error) {
                    [self endDataLoad:success :statusCode :responseHeader :responseBody :error];
                        if (success){
                            [self showData];
                            [self updateData];
                        }
                        else {
                            // 元に戻す
                            APPDELEGATE.me.profile.name = nameLabel.text;
                        }
                        [argVC onPushBackButton:nil];
                }];
            }
            else {
                [argVC onPushBackButton:nil];
            }
        }];
    }
}


#pragma mark - UIImagePickerControllerDelegate Methods

- (void)imagePickerController:(UIImagePickerController*)picker didFinishPickingImage:(UIImage*)image editingInfo:(NSDictionary*)editingInfo
{
    int cropImageWidth = 300;
    int cropImageHeight = 300;
    UIView *trimingOverlayView = [[UIView alloc] initWithFrame:APPDELEGATE.window.frame];
    trimingOverlayView.userInteractionEnabled = NO;
    // トリムボーダーをオーバーレイ
    UIView *trimBorderView = [[UIView alloc] initWithFrame:CGRectMake((self.view.frame.size.width - 300)/2.0, (APPDELEGATE.window.frame.size.height - 300)/2.0, 300, 300)];
    [trimBorderView.layer setBorderColor:[UIColor whiteColor].CGColor];
    [trimBorderView.layer setBorderWidth:1.0];
    trimBorderView.userInteractionEnabled = NO;
    [trimingOverlayView addSubview:trimBorderView];
    // トリミングを呼ぶ
    [APPDELEGATE addSubviewFirstFront:[[MCropImageView alloc] initWithFrame:APPDELEGATE.window.frame :image :cropImageWidth :cropImageHeight :YES :trimingOverlayView :^(MCropImageView *mcropImageView, BOOL finished, UIImage *image) {
        if(YES == finished && nil != image && [image isKindOfClass:NSClassFromString(@"UIImage")]){
            // 画像保存処理
            [self startDataLoad];
            NSString *tmpImageStr = [[[NSData alloc] initWithData:UIImageJPEGRepresentation(image, 0.5)] base64EncodedStringWithOptions:NSDataBase64Encoding76CharacterLineLength];
            APPDELEGATE.me.profile.image = tmpImageStr;
            [APPDELEGATE.me.profile save:^(BOOL success, NSInteger statusCode, NSHTTPURLResponse *responseHeader, NSString *responseBody, NSError *error) {
                    [self endDataLoad:success :statusCode :responseHeader :responseBody :error];
                    if (YES == success) {
                        // 再描画
                        [self showData];
                        [self updateData];
                    }
                }];
        }
        // トリミング画面非表示
        [mcropImageView dissmiss:YES];
        [APPDELEGATE removeFromFirstFrontSubview:mcropImageView];
    }]];
    [picker dismissViewControllerAnimated:NO completion:nil];
}


@end
