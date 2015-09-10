//
//  AppDelegate.h
//
//  Created by saimushi on 2014/09/19.
//  Copyright (c) 2014年 saimushi. All rights reserved.
//

#import "common.h"

@interface AppDelegate : UIResponder <UIApplicationDelegate, UITabBarControllerDelegate, ModelDelegate>
{
    // Protected
}

// Public
@property (strong, nonatomic) UIWindow *window;
@property (strong, nonatomic) UserModel *me;

- (void)registerDeviceToken;
- (void)initializeGoogleAnalytics;
- (BOOL)isSimulator;
- (void)addSubviewFirstFront:(UIView *)argView;
- (void)removeFromFirstFrontSubview:(UIView *)argView;
- (void)showLoading:(NSString *)argLoadingMessage;
- (void)showLoading;
- (void)hideLoading;

@end
