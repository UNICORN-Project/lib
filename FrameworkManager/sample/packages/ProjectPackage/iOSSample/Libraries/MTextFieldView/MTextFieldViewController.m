//
//  MTextFieldViewController.m
//
//  Created by saimushi on 2014/09/19.
//
#import "MTextFieldViewController.h"
#import "MTextFieldView.h"

@interface MTextFieldViewController ()
{
    BOOL isNavigation;
    NSString *title;
    NSString *inputString;
    int numberOfLine;
    int minLength;
    int maxLength;
    BOOL isSecure;
    UIKeyboardType defaultkeyboardType;
    void (^saveCompletion)(NSString *argInputText, MTextFieldViewController *argVC);
    NSRange latestRange;
    BOOL editing;
    UIColor *errorColor;
}
@end

@implementation MTextFieldViewController

@synthesize inputView;

+ (MTextFieldViewController *)show:(BOOL)argPush :(UIViewController *)argTarget :(NSString *)argTitle :(NSString *)argInputString :(int)argNumberOfLine :(int)argMinLength :(int)argMaxLength :(BOOL)argSecure :(UIKeyboardType)argKeyboardType :(void(^)(NSString *argInputText, MTextFieldViewController *argVC))argCompletion;
{
    MTextFieldViewController *textVC = [[MTextFieldViewController alloc] init:argPush :argTitle :argInputString :argNumberOfLine :argMinLength :argMaxLength :argSecure :argKeyboardType :argCompletion];
    if (argPush){
        [argTarget.navigationController pushViewController:textVC animated:YES];
    }
    else {
        [argTarget presentViewController:[[UINavigationController alloc] initWithRootViewController:textVC] animated:YES completion:nil];
    }
    return textVC;
}

- (id)init:(BOOL)argIsNav :(NSString *)argTitle :(NSString *)argInputString :(int)argNumberOfLine :(int)argMinLength :(int)argMaxLength :(BOOL)argSecure :(UIKeyboardType)argKeyboardType :(void(^)(NSString *argInputText, MTextFieldViewController *argVC))argCompletion;
{
    self = [super init];
    if(self != nil){
        isNavigation = argIsNav;
        title = argTitle;
        inputString = argInputString;
        numberOfLine = argNumberOfLine;
        minLength = argMinLength;
        maxLength = argMaxLength;
        isSecure = argSecure;
        defaultkeyboardType = argKeyboardType;
        saveCompletion = argCompletion;
        editing = NO;
    }
    return self;
}

- (void)loadView
{
    [super loadView];

    // latestNSRangeの初期化
    latestRange = NSRangeFromString(@"");

    // ナビゲーションバーの設定
    self.navigationItem.title = title;
    // 閉じるボタン
    if (YES != isNavigation){
        self.navigationItem.rightBarButtonItem =  [[UIBarButtonItem alloc] initWithTitle:NSLocalizedString(@"Close", @"閉じる") style:UIBarButtonItemStylePlain target:self action:@selector(onPushBackButton:)];
    }

    CGRect originalFrame = self.view.frame;
    // 5.5インチHD対応
    if (400 < self.view.frame.size.width){
        self.view.frame = CGRectMake(self.view.frame.origin.x, self.view.frame.origin.y, self.view.frame.size.width, self.view.frame.size.height - 20);
    }
    // 4.7インチHD対応
    else if (320 < self.view.frame.size.width){
        self.view.frame = CGRectMake(self.view.frame.origin.x, self.view.frame.origin.y, self.view.frame.size.width, self.view.frame.size.height - 10);
    }

    self.inputView = [[MTextFieldView alloc] initWithFrame:CGRectMake(0, self.navigationController.navigationBar.frame.size.height + 20, self.view.frame.size.width, self.view.frame.size.height - self.navigationController.navigationBar.frame.size.height - 64) :inputString :numberOfLine :self];
    self.inputView.onelineField.secureTextEntry = isSecure;
    self.inputView.multilineField.secureTextEntry = isSecure;
    self.inputView.onelineField.keyboardType = defaultkeyboardType;
    self.inputView.multilineField.keyboardType = defaultkeyboardType;
    self.inputView.errorLabel.text =[NSString stringWithFormat:NSLocalizedString(@"Limit String Length %d ~ %d", @"文字数:%d〜%d"), minLength, maxLength];
    [self.inputView.saveBtn addTarget:self action:@selector(onPushComplteButton:) forControlEvents:UIControlEventTouchUpInside];
    [self.view addSubview:self.inputView];

    // 背景再設定
    self.view.frame = originalFrame;
    self.view.backgroundColor = self.inputView.btnAreaView.backgroundColor;
    errorColor = self.inputView.errorLabel.textColor;
    self.inputView.errorLabel.textColor = self.inputView.saveBtn.titleLabel.textColor;
}

- (void)viewDidLoad
{
    [super viewDidLoad];
    // 初回の文字数チェックによる表示制御処理
    if (nil != inputString && 0 < [inputString length]){
        [self resolveCheckInputlimit:inputString];
    }
}

- (void)didReceiveMemoryWarning
{
    [super didReceiveMemoryWarning];
    // Dispose of any resources that can be recreated.
}

- (void)onPushComplteButton:(id)sender;
{
    if(1 == numberOfLine){
        inputString = self.inputView.onelineField.text;
    }
    else {
        inputString = self.inputView.multilineField.text;
    }
    // 保存処理
    if (YES == self.inputView.saveBtn.enabled){
        saveCompletion(inputString, self);
    }
}

- (void)onPushBackButton:(id)sender;
{
    if(1 == numberOfLine){
        [self.inputView.onelineField resignFirstResponder];
    }
    else {
        [self.inputView.multilineField resignFirstResponder];
    }
    if (isNavigation){
        // 戻る
        [self.navigationController popViewControllerAnimated:YES];
    }
    else {
        // 閉じる
        [self dismissViewControllerAnimated:YES completion:nil];
    }
}

- (void)resolveCheckInputlimit:(NSString*)argText;
{
    // 文字数判定
    if(minLength <= [argText length] && maxLength >= [argText length]){
        self.inputView.saveBtn.enabled = YES;
        self.inputView.errorLabel.textColor = self.inputView.saveBtn.titleLabel.textColor;
        self.inputView.errorLabel.text =[NSString stringWithFormat:NSLocalizedString(@"Limit String Length %d ~ %d", @"文字数:%d〜%d"), minLength, maxLength];
    }
    else {
        self.inputView.saveBtn.enabled = NO;
        self.inputView.errorLabel.textColor = errorColor;
        if(maxLength < [argText length]){
            self.inputView.errorLabel.text =[NSString stringWithFormat:NSLocalizedString(@"Limit Over String %d", @"文字数が%d文字オーバーしています"), (int)([argText length] - maxLength)];
        }
        else if (minLength > [argText length]){
            self.inputView.errorLabel.text =[NSString stringWithFormat:NSLocalizedString(@"Limit Under String %d", @"文字数が%d文字不足しています"), (int)(minLength - [argText length])];
        }
    }
}

#pragma mark - UITextFieldDelegate Methods

- (BOOL)textFieldShouldReturn:(UITextField *)textField
{
    [self onPushComplteButton:nil];
    return YES;
}

- (BOOL)textField:(UITextField *)argTextField shouldChangeCharactersInRange:(NSRange)range replacementString:(NSString *)text
{
    // 実際に UITextView に入力されている文字数
    // 入力済みのテキストを取得
    NSMutableString *str = [argTextField.text mutableCopy];
    
    // 入力途中のバックスペース判定
    if (0 == text.length && 0 < latestRange.location && 1 < range.length && latestRange.location == range.location + range.length - 1) {
        // バックスペースレンジに変更
        range = NSMakeRange(latestRange.location -1, 1);
    }
    
    // 改行は含めない
    if (![text isEqualToString:@"\n"]){
        // 入力済みのテキストと入力が行われた(行われる)テキストを結合
        [str replaceCharactersInRange:range withString:text];
    }
    
    // 文字数判定
    [self resolveCheckInputlimit:str];

    // バックスペース判定用に以前のrangeを取っておく
    latestRange = range;
    return YES;
}

#pragma mark - UITextViewDelegate Methods

- (BOOL)textView:(UITextView *)argTextView shouldChangeTextInRange:(NSRange)range replacementText:(NSString *)text
{
    // 実際に UITextView に入力されている文字数
    // 入力済みのテキストを取得
    NSMutableString *str = [argTextView.text mutableCopy];
    
    // 入力途中のバックスペース判定
    if (0 == text.length && 0 < latestRange.location && 1 < range.length && latestRange.location == range.location + range.length - 1) {
        // バックスペースレンジに変更
        range = NSMakeRange(latestRange.location -1, 1);
    }
    
    // 入力済みのテキストと入力が行われた(行われる)テキストを結合
    [str replaceCharactersInRange:range withString:text];

    // 文字数判定
    [self resolveCheckInputlimit:str];
    
    // バックスペース判定用に以前のrangeを取っておく
    latestRange = range;
    return YES;
}

@end
