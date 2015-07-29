//
//  HowaboutViewController.m
//
//  Created by saimushi on 2014/09/19.
//  Copyright (c) 2014年 saimushi. All rights reserved.
//

#import "HowaboutViewController.h"

@interface HowaboutViewController ()
{
    // Private
}
@end

@implementation HowaboutViewController

- (void)viewDidLoad
{
    // デフォルトのスクリーン名をセット
    screenName = self.navigationItem.title;
    [super viewDidLoad];
    // WebViewにライセンスページを表示
    [self.webView loadRequest:[NSURLRequest requestWithURL:[NSURL URLWithString:@"http://unicorn-project.github.io/licenses.html"]]];
}

#pragma mark UIWebView Delegate

// ページ読込開始時にインジケータをくるくるさせる
-(void)webViewDidStartLoad:(UIWebView*)webView
{
    [APPDELEGATE showLoading];
}

// ページ読込完了時にインジケータを非表示にする
-(void)webViewDidFinishLoad:(UIWebView*)webView
{
    [APPDELEGATE hideLoading];
}

@end
