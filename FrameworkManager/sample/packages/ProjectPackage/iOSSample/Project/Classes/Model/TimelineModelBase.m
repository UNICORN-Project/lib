//
//  TimelineModelBase.m
//
//  Copyright (c) 2014年 saimushi. All rights reserved.
//

#import "TimelineModelBase.h"
#import "ProfileModel.h"


@implementation TimelineModelBase
{
    BOOL text_replaced;
    BOOL profile_id_replaced;
    BOOL owner_id_replaced;
    BOOL created_replaced;
    BOOL modified_replaced;
    BOOL available_replaced;

}

@synthesize text;
@synthesize profile_id;
@synthesize profile;
@synthesize owner_id;
@synthesize created;
@synthesize modified;
@synthesize available;


-(void)setText:(NSString *)argText
{
    text = argText;
    text_replaced = YES;
    replaced = YES;
}

-(void)setProfile_id:(NSString *)argProfile_id
{
    profile_id = argProfile_id;
    profile_id_replaced = YES;
    replaced = YES;
}

-(void)setOwner_id:(NSString *)argOwner_id
{
    owner_id = argOwner_id;
    owner_id_replaced = YES;
    replaced = YES;
}

-(void)setCreated:(NSString *)argCreated
{
    created = argCreated;
    created_replaced = YES;
    replaced = YES;
}

-(void)setModified:(NSString *)argModified
{
    modified = argModified;
    modified_replaced = YES;
    replaced = YES;
}

-(void)setAvailable:(NSString *)argAvailable
{
    available = argAvailable;
    available_replaced = YES;
    replaced = YES;
}



/* オーバーライド */
- (id)init:(NSString *)argProtocol :(NSString *)argDomain :(NSString *)argURLBase :(NSString *)argTokenKeyName;
{
    self = [super init:argProtocol :argDomain :argURLBase :argTokenKeyName];
    if(nil != self){
        modelName = @"timeline";
        text_replaced = NO;
        profile_id_replaced = NO;
        owner_id_replaced = NO;
        created_replaced = NO;
        modified_replaced = NO;
        available_replaced = NO;

    }
    return self;
}

/* オーバーライド */
- (BOOL)save;
{
    if(YES == replaced){
        NSMutableDictionary *saveParams = [[NSMutableDictionary alloc] init];
        if(YES == text_replaced){
            [saveParams setValue:self.text forKey:@"text"];
        }
        if(YES == profile_id_replaced){
            [saveParams setValue:self.profile_id forKey:@"profile_id"];
        }
        if(YES == owner_id_replaced){
            [saveParams setValue:self.owner_id forKey:@"owner_id"];
        }
        if(YES == created_replaced){
            [saveParams setValue:self.created forKey:@"created"];
        }
        if(YES == modified_replaced){
            [saveParams setValue:self.modified forKey:@"modified"];
        }
        if(YES == available_replaced){
            [saveParams setValue:self.available forKey:@"available"];
        }

        return [super _save:saveParams];
    }
    // 何もしないで終了
    return YES;
}

- (NSMutableDictionary *)convertModelData;
{
    NSMutableDictionary *newDic = [[NSMutableDictionary alloc] init];
    [newDic setObject:self.ID forKey:@"id"];
    [newDic setObject:self.text forKey:@"text"];
    [newDic setObject:self.profile_id forKey:@"profile_id"];
    /* ProfileモデルのDEEP-REST */
    NSMutableArray *profileList = [[NSMutableArray alloc] init];
    if(0 < self.profile.total){
        do {
            [profileList addObject:[self.profile convertModelData]];
        } while (YES == [self.profile next]);
    }
    [newDic setObject:profileList forKey:@"profile"];
    [newDic setObject:self.owner_id forKey:@"owner_id"];
    [newDic setObject:self.created forKey:@"created"];
    [newDic setObject:self.modified forKey:@"modified"];
    [newDic setObject:self.available forKey:@"available"];

    return newDic;
}

- (void)_setModelData:(NSMutableDictionary *)argDataDic;
{
    self.ID = [argDataDic objectForKey:@"id"];
    self.text = [argDataDic objectForKey:@"text"];
    self.profile_id = [argDataDic objectForKey:@"profile_id"];
    /* ProfileモデルのDEEP-REST */
    NSMutableArray *profileDic = [argDataDic objectForKey:@"profile"];
    if(nil != profileDic){
        self.profile = [[ProfileModel alloc] init:protocol :domain :urlbase :tokenKeyName :cryptKey :cryptIV :timeout];
        [self.profile setModelData:[argDataDic objectForKey:@"profile"]];
    }
    self.owner_id = [argDataDic objectForKey:@"owner_id"];
    self.created = [argDataDic objectForKey:@"created"];
    self.modified = [argDataDic objectForKey:@"modified"];
    self.available = [argDataDic objectForKey:@"available"];

    [self resetReplaceFlagment];
}

- (void)resetReplaceFlagment;
{
    text_replaced = NO;
    profile_id_replaced = NO;
    owner_id_replaced = NO;
    created_replaced = NO;
    modified_replaced = NO;
    available_replaced = NO;

    replaced = NO;
    return;
}

@end
