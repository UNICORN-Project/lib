//
//  UIStaticTableViewControllerBase.h
//
//  Created by saimushi on 2012/10/30.
//  Copyright (c) 2012年 saimushi. All rights reserved.
//

#import "UIStaticTableViewControllerBase.h"
#import "MProgress.h"

@interface UIStaticTableViewControllerBase()
{
    // Private
}
@end

@implementation UIStaticTableViewControllerBase

@synthesize data;


#pragma mark Custom Methods

- (void)showData:(NSHTTPURLResponse *)argResponseHeader :(NSString *)argResponseBody;
{
}

- (void)showLoading:(NSString *)argLoadingMessage;
{
    if ([[UIApplication sharedApplication].delegate respondsToSelector:@selector(showLoading:)]){
        [[UIApplication sharedApplication].delegate performSelector:@selector(showLoading:) withObject:argLoadingMessage];
    }
    else {
        [UIApplication sharedApplication].networkActivityIndicatorVisible = YES;
        [MProgress showProgressWithLoadingText:argLoadingMessage];
    }
}

- (void)hideLoading;
{
    if ([[UIApplication sharedApplication].delegate respondsToSelector:@selector(hideLoading)]){
        [[UIApplication sharedApplication].delegate performSelector:@selector(hideLoading) withObject:nil];
    }
    else {
        [UIApplication sharedApplication].networkActivityIndicatorVisible = NO;
        [MProgress dismissProgress];
    }
}

#pragma mark Dummy Methods

- (void)dataLoad;
{
    [self startDataLoad];
    [self.data load:resourceMode :^(BOOL success, NSInteger statusCode, NSHTTPURLResponse *responseHeader, NSString *responseBody, NSError *error) {
        [self endDataLoad:success :statusCode :responseHeader :responseBody :error];
    }];
}

- (void)startDataLoad;
{
    _loading = YES;
    [self showLoading:loadingMessage];
}

- (void)endDataLoad:(BOOL)argSuccess :(NSInteger)argStatusCode :(NSHTTPURLResponse *)argResponseHeader :(NSString *)argResponseBody :(NSError *)argError;
{
    _loading = NO;
    [self hideLoading];
    if (argSuccess && 200 == argStatusCode){
        [self showData:argResponseHeader :argResponseBody];
    }
    else {
        // エラー処理をするならココ
    }
}


#pragma mark UIView Controller Event Methods

- (id)init
{
    self = [super init];
    if(self != nil){
        isNavigateion = YES;
        loadingMessage = NSLocalizedString(@"Loading...", @"読み込み中...");
        resourceMode = listedResource;
    }
    return self;
}

- (id)initWithCoder:(NSCoder*)aDecoder
{
    self = [super initWithCoder:aDecoder];
    if(self != nil){
        isNavigateion = YES;
        loadingMessage = NSLocalizedString(@"Loading...", @"読み込み中...");
        resourceMode = listedResource;
    }
    return self;
}

- (void)viewDidAppear:(BOOL)animated
{
    [super viewDidAppear:animated];
    if (nil == screenName){
        screenName = @"UIStaticTableViewControllerBase";
        if (isNavigateion){
            screenName = self.navigationItem.title;
        }
    }
//    [TrackingManager sendScreenTracking:screenName];
    viewStayStartTime = [NSDate date];
    if (isNavigateion){
        self.navigationItem.title = screenName;
    }
    else {
        if (nil != self.navigationItem){
            self.navigationController.navigationBarHidden = YES;
        }
    }
}

- (void)viewDidDisappear:(BOOL)animated
{
    // View滞在時間を計測
    viewStayEndTime = [NSDate date];
    // Google Analytics
//    [TrackingManager sendEventTracking:@"StayViewTime" action:screenName label:@"ViewStayTime" value:[[NSNumber alloc]initWithInt:(int)[viewStayEndTime timeIntervalSinceDate:viewStayStartTime]] screen:screenName];
    [super viewDidDisappear:animated];
}


- (void)didReceiveMemoryWarning
{
    [super didReceiveMemoryWarning];
    // Dispose of any resources that can be recreated.
}


@end
