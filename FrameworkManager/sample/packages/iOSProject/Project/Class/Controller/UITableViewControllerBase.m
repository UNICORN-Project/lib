//
//  UITableViewControllerBase.h
//
//  Created by saimushi on 2015/07/12.
//  Copyright (c) 2015 saimushi. All rights reserved.
//

#import "UITableViewControllerBase.h"

@interface UITableViewControllerBase()
{
    // Private
}
@end

@implementation UITableViewControllerBase


#pragma mark Custom Methods

#pragma mark Dummy Methods

- (void)getName
{
}

#pragma mark Data Load Methods
/* orver ride */
- (void)showData:(NSHTTPURLResponse *)argResponseHeader :(NSString *)argResponseBody;
{
    [self.dataListView reloadData];    
}

/* orver ride */
- (void)dataLoad;
{
    if (nil != self.data){
        if ([searchText isEqualToString:@""]){
            [super dataLoad];
        }
        else {
            [self search:searchText];
        }
    }
}

/* orver ride */
- (void)endDataLoad:(BOOL)argSuccess :(NSInteger)argStatusCode :(NSHTTPURLResponse *)argResponseHeader :(NSString *)argResponseBody :(NSError *)argError;
{
    [super endDataLoad:argSuccess :argStatusCode :argResponseHeader :argResponseBody :argError];
    self.dataListView.hidden = NO;
    [_refreshHeaderView egoRefreshScrollViewDataSourceDidFinishedLoading:self.dataListView];
}

- (void)dataListLoad;
{
    // offsetを初期化してロードする
    self.data.offset = 0;
    [self dataLoad];
}

- (void)dataListAddLoad;
{
    // 追加なのでoffsetの初期化はしない
    [self dataLoad];
}

- (UITableViewCell *)generateDataCell:(UITableViewCell *)argCell :(ModelBase *)argModel;
{
    if ([argModel respondsToSelector:@selector(getName)]){
        argCell.textLabel.text = [argModel performSelector:@selector(getName) withObject:nil];
    }
    return argCell;
}


#pragma mark Data Search Methods

- (void)search:(NSString *)argSearchText;
{
    if (nil != self.data){
        [self startDataLoad];
        NSMutableDictionary *whereParams = [[NSMutableDictionary alloc] init];
        [whereParams setValue:[self generateSearchQuery:argSearchText] forKey:@"QUERY"];
        [self.data query:whereParams :resourceMode :^(BOOL success, NSInteger statusCode, NSHTTPURLResponse *responseHeader, NSString *responseBody, NSError *error) {
            [self endDataLoad:success :statusCode :responseHeader :responseBody :error];
        }];
    }
}

- (void)endSearch;
{
    if (nil != searchBar){
        // 検索処理
        if (![searchText isEqualToString:searchBar.text]){
            if ([searchBar.text isEqualToString:@""]){
                // 検索の終了
                searchText = @"";
                [self dataListLoad];
            }
            else {
                // 検索
                self.data.offset = 0;
                [self search:searchBar.text];
            }
        }
        searchText = searchBar.text;
    }
    [tapAreaAlphaBtn removeFromSuperview];
    [searchBar resignFirstResponder];
}

- (NSString *)generateSearchQuery:(NSString *)argSearchText;
{
    return [NSString stringWithFormat:@"name LIKE '%@%@%@'", @"%", argSearchText, @"%"];
}


#pragma mark UIView Controller Event Methods

- (id)init
{
    self = [super init];
    if(self != nil){
        _loading = NO;
        dataCellIdentifier = @"dataCell";
        nodataCellIdentifier = @"nodataCell";
        showDetailSegueIdentifier = @"showDetail";
        searchText = @"";
        resourceMode = listedResource;
    }
    return self;
}

- (id)initWithCoder:(NSCoder*)aDecoder
{
    self = [super initWithCoder:aDecoder];
    if(self != nil){
        _loading = NO;
        dataCellIdentifier = @"dataCell";
        nodataCellIdentifier = @"nodataCell";
        showDetailSegueIdentifier = @"showDetail";
        searchText = @"";
        resourceMode = listedResource;
    }
    return self;
}

- (void)viewDidLoad
{
    // PullToRefreshを設定
    _refreshHeaderView = [[EGORefreshTableHeaderView alloc] initWithFrame:self.dataListView.tableHeaderView.frame];
    _refreshHeaderView.delegate = self;
    _refreshHeaderView.backgroundColor = [UIColor clearColor];
    self.dataListView.tableHeaderView = _refreshHeaderView;
    self.dataListView.hidden = YES;
    [super viewDidLoad];
}


#pragma mark Segue Delegate

- (BOOL)shouldPerformSegueWithIdentifier:(NSString *)identifier sender:(id)sender
{
    if ([identifier isEqualToString:showDetailSegueIdentifier]) {
        // 選択セルの詳細情報表示処理
        NSIndexPath *indexPath = [self.dataListView indexPathForSelectedRow];
        // 選択セルの情報はとれたかな？ _chatListはNSArray的なやつで、isEmptyはnil & countチェックメソッドdeath!
        if (0 >= self.data.total || ![self.data objectAtIndex:(int)indexPath.row]) {
            return NO;
        }
    }
    return YES;
}

- (void)prepareForSegue:(UIStoryboardSegue *)segue sender:(id)sender
{
    if ([segue.identifier isEqualToString:showDetailSegueIdentifier]) {
        // 選択セルの詳細情報表示処理
        if (nil != searchBar){
            [searchBar resignFirstResponder];
        }
        UIViewControllerBase *detailViewController = segue.destinationViewController;
        if ([detailViewController isKindOfClass:NSClassFromString(@"UINavigationController")]){
            detailViewController = (UIViewControllerBase *)((UINavigationController *)segue.destinationViewController).visibleViewController;
        }
        if ([detailViewController respondsToSelector:@selector(setData:)]){
            NSIndexPath *indexPath = [self.dataListView indexPathForSelectedRow];
            detailViewController.data = (ModelBase *)[self.data objectAtIndex:(int)indexPath.row];
        }
    }
}


#pragma mark TableView Delegate

- (CGFloat)tableView:(UITableView *)tableView heightForRowAtIndexPath:(NSIndexPath *)indexPath
{
    if (0 < self.data.total) {
        return tableView.rowHeight;
    }
    // デフォルトのEmpty表示用
    return tableView.frame.size.height;
}

- (NSInteger)tableView:(UITableView *)tableView numberOfRowsInSection:(NSInteger)section
{
    if (0 < self.data.total) {
        self.dataListView.allowsSelection = YES;
        return self.data.total;
    }
    // デフォルトのEmpty表示用
    self.dataListView.allowsSelection = NO;
    return 1;
}

- (UITableViewCell *)tableView:(UITableView *)tableView cellForRowAtIndexPath:(NSIndexPath *)indexPath
{
    UITableViewCell *cell = nil;
    if(0 < self.data.total){
        cell = [self generateDataCell:[tableView dequeueReusableCellWithIdentifier:dataCellIdentifier forIndexPath:indexPath]  :[self.data objectAtIndex:(int)indexPath.row]];
    }
    else {
        cell = [[UITableViewCell alloc] init];
        UIView *nodataCellView = (UIView *)[[[NSBundle mainBundle] loadNibNamed:nodataCellIdentifier owner:nil options:0] firstObject];
        nodataCellView.frame = CGRectMake(0, 0, self.dataListView.frame.size.width, self.dataListView.frame.size.height);
        nodataCellView.backgroundColor = [UIColor clearColor];
        cell.contentView.frame = nodataCellView.frame;
        [cell.contentView addSubview:nodataCellView];
    }
    return cell;
}

-(void)tableView:(UITableView*)tableView didSelectRowAtIndexPath:(NSIndexPath *)indexPath
{
    // 選択解除
    [tableView deselectRowAtIndexPath:indexPath animated:YES];
    // 詳細表示
    if (nil != showDetailSegueIdentifier && 0 < [showDetailSegueIdentifier length]){
        [self performSegueWithIdentifier:showDetailSegueIdentifier sender:self];
    }
}

-(void)tableView:(UITableView*)tableView willDisplayCell:(UITableViewCell *)cell forRowAtIndexPath:(NSIndexPath *)indexPath
{
    if(0 < self.data.total && self.data.total < self.data.records){
        if(YES == (((int)indexPath.row) + 1 >= self.data.total)){
            // 追加読み込み
            [self dataListAddLoad];
        }
    }
}


#pragma mark - UIScrollViewDelegate Methods

- (void)scrollViewDidScroll:(UIScrollView *)scrollView
{
    [_refreshHeaderView egoRefreshScrollViewDidScroll:scrollView];
}

- (void)scrollViewDidEndDragging:(UIScrollView *)scrollView willDecelerate:(BOOL)decelerate
{
    [_refreshHeaderView egoRefreshScrollViewDidEndDragging:scrollView];
}


#pragma mark - EGORefreshTableHeaderDelegate Methods

- (void)egoRefreshTableHeaderDidTriggerRefresh:(EGORefreshTableHeaderView*)view
{
    // テーブルView Refresh
    [self dataListLoad];
}

- (BOOL)egoRefreshTableHeaderDataSourceIsLoading:(EGORefreshTableHeaderView*)view
{
    return _loading; // should return if data source model is reloading
}

- (NSDate*)egoRefreshTableHeaderDataSourceLastUpdated:(EGORefreshTableHeaderView*)view
{
    return [NSDate date]; // should return date data source was last changed
}


#pragma mark - UISearchBarDelegate Methods

- (BOOL)searchBarShouldBeginEditing:(UISearchBar *)argSearchBar
{
    if (nil == searchBar){
        searchBar = argSearchBar;
    }
    if (nil == searchText){
        searchText = @"";
    }
    if ([searchBar.text isEqualToString:@""] && ![searchText isEqualToString:searchBar.text]) {
        //Clear stuff here
        [self endSearch];
        // 編集モードにせずに終了
        return NO;
    }
    tapAreaAlphaBtn = [UIButton buttonWithType:UIButtonTypeCustom];
    tapAreaAlphaBtn.frame = self.view.frame;
    tapAreaAlphaBtn.backgroundColor = [UIColor colorWithRed:0.0f green:0.0f blue:0.0f alpha:0.4f];
    [tapAreaAlphaBtn addTarget:self action:@selector(endSearch) forControlEvents:UIControlEventTouchUpInside];
    [self.view addSubview:tapAreaAlphaBtn];
    return YES;
}

- (BOOL)searchBar:(UISearchBar *)argSearchBar shouldChangeTextInRange:(NSRange)range replacementText:(NSString *)text
{
    if (nil == searchBar){
        searchBar = argSearchBar;
    }
    if (nil == searchText){
        searchText = @"";
    }
    return YES;
}

- (void)searchBarSearchButtonClicked:(UISearchBar *)argSearchBar
{
    if (nil == searchBar){
        searchBar = argSearchBar;
    }
    if (nil == searchText){
        searchText = @"";
    }
    [self endSearch];
}

-(void)searchBarCancelButtonClicked:(UISearchBar*)argSearchBar
{
    if (nil == searchBar){
        searchBar = argSearchBar;
    }
    if (nil == searchText){
        searchText = @"";
    }
    [self endSearch];
}

@end
