//
//  TimelineModelBase.h
//
//  Copyright (c) 2014年 saimushi. All rights reserved.
//

#import "ProjectModelBase.h"

@class ProfileModel;


@interface TimelineModelBase : ProjectModelBase
{
    NSString *text;
    NSString *profile_id;
    /* ProfileモデルのDEEP-RESTモデル */
    ProfileModel *profile;
    NSString *owner_id;
    NSString *created;
    NSString *modified;
    NSString *available;

}

@property (strong, nonatomic) NSString *text;
@property (strong, nonatomic) NSString *profile_id;
@property (strong, nonatomic) ProfileModel *profile;
@property (strong, nonatomic) NSString *owner_id;
@property (strong, nonatomic) NSString *created;
@property (strong, nonatomic) NSString *modified;
@property (strong, nonatomic) NSString *available;


@end
