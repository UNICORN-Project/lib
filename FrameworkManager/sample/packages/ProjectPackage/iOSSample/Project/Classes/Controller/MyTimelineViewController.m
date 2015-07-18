//
//  MyTimelineViewController.m
//
//  Created by saimushi on 2015/07/14.
//  Copyright (c) 2015 saimushi. All rights reserved.
//

#import "MyTimelineViewController.h"
#import "TimelineModel.h"

@interface MyTimelineViewController ()
{
    // Private
}
@end

@implementation MyTimelineViewController


#pragma mark Over ride Custom Methods

- (UITableViewCell *)generateDataCell:(UITableViewCell *)argCell :(TimelineModel *)argModel;
{
    // 一言
    ((UILabel *)[argCell.contentView viewWithTag:1]).text = argModel.text;
    // 日時
    ((UILabel *)[argCell.contentView viewWithTag:2]).text = argModel.modified;
    return argCell;
}


#pragma mark UIView Controller Event Methods

- (id)init
{
    self = [super init];
    if(self != nil){
        dataCellIdentifier = @"myTimelineTableCell";
        nodataCellIdentifier = @"NodataCellView";
        resourceMode = myListedResource;
    }
    return self;
}

- (id)initWithCoder:(NSCoder*)aDecoder
{
    self = [super initWithCoder:aDecoder];
    if(self != nil){
        dataCellIdentifier = @"myTimelineTableCell";
        nodataCellIdentifier = @"NodataCellView";
        resourceMode = myListedResource;
    }
    return self;
}

- (void)viewDidLoad
{
    // モデルクラス初期化
    self.data.isDeep = NO;
    self.data.limit = 20;
    // 初期化
    [super viewDidLoad];
}


@end
