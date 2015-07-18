//
//  UITableViewControllerBase.h
//
//  Created by saimushi on 2015/07/12.
//  Copyright (c) 2015 saimushi. All rights reserved.
//

#import "UIViewControllerBase.h"
#import "EGORefreshTableHeaderView.h"

@interface UITableViewControllerBase : UIViewControllerBase <UISearchDisplayDelegate, UISearchBarDelegate, UITableViewDelegate, UITableViewDataSource, EGORefreshTableHeaderDelegate, UIScrollViewDelegate>
{
    // Protected
    UISearchBar *searchBar;
    NSString *searchText;
    EGORefreshTableHeaderView *_refreshHeaderView;
    NSString *dataCellIdentifier;
    NSString *nodataCellIdentifier;
    NSString *showDetailSegueIdentifier;
}

// Public
@property (weak, nonatomic) IBOutlet UITableView *dataListView;
- (void)dataListLoad;
- (void)dataListAddLoad;
-(void)search:(NSString *)argSearchText;
-(void)endSearch;

@end
