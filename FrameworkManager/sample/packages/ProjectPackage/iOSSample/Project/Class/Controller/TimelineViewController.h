//
//  TimelineViewController.h
//
//  Created by saimushi on 2014/09/19.
//  Copyright (c) 2014年 saimushi. All rights reserved.
//

#import "common.h"

@class TimelineModel;

@interface TimelineViewController : UITableViewControllerBase
{
    // Protected
}

// Public
- (IBAction)add:(id)sender;
// データをローカルで追加して再描画
- (void)addDataAndReloadView:(TimelineModel *)argModel;

@end
