//
//  UIViewControllerBase.h
//
//  Created by saimushi on 2012/10/30.
//  Copyright (c) 2012年 saimushi. All rights reserved.
//

#import "ModelBase.h"

@interface UIViewControllerBase : UIViewController <UINavigationControllerDelegate>
{
    // Protected
    NSDate *viewStayStartTime;
    NSDate *viewStayEndTime;
    NSString *screenName;
    BOOL isNavigateion;
    loadResourceMode resourceMode;
    ModelBase *data;
    UIButton *tapAreaAlphaBtn;
    BOOL _loading;
    NSString *loadingMessage;
}

// Public
@property (strong, nonatomic) ModelBase *data;

- (void)setModelData:(ModelBase *)argModelData;
- (void)showData:(NSHTTPURLResponse *)argResponseHeader :(NSString *)argResponseBody;
- (void)showLoading:(NSString *)argLoadingMessage;
- (void)hideLoading;
- (void)dataLoad;
- (void)startDataLoad;
- (void)endDataLoad:(BOOL)argSuccess :(NSInteger)argStatusCode :(NSHTTPURLResponse *)argResponseHeader :(NSString *)argResponseBody :(NSError *)argError;

@end
