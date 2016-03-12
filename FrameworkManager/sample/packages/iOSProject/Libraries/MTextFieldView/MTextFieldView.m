//
//  MTextFieldView.m
//  GMatch
//
//  Created by saimushi on 2014/09/21.
//
#import "MTextFieldView.h"
#import "MTextFieldViewController.h"

@implementation MTextFieldView
{
}

@synthesize bgView;
@synthesize onelineField;
@synthesize multilineField;
@synthesize saveBtn;
@synthesize errorLabel;
@synthesize btnAreaView;

- (id)initWithFrame:(CGRect)argFrame :(NSString *)argInputString :(int)argNumberOfLine :(MTextFieldViewController *)argTarget;
{
    self = [super initWithFrame:argFrame];
    if (self) {        
        self.backgroundColor = [UIColor clearColor];
        self.bgView = [[UIView alloc] initWithFrame:CGRectMake(0, 0, self.frame.size.width, self.frame.size.height - 209)];
        self.bgView.backgroundColor = [UIColor whiteColor];
        [self addSubview:self.bgView];

        if(1 == argNumberOfLine){
            // 一行の時はUITextFieldに変更
            self.onelineField = [[UITextField alloc] initWithFrame:CGRectMake(10, 10, self.frame.size.width - 20, 30)];
            self.onelineField.text = argInputString;
            self.onelineField.textColor = [UIColor colorWithRed:0.40 green:0.40 blue:0.40 alpha:1.0];
            self.onelineField.font = [UIFont systemFontOfSize:14];
            self.onelineField.textAlignment = NSTextAlignmentLeft;
            self.onelineField.returnKeyType = UIReturnKeyDone;
            self.onelineField.clearsOnBeginEditing = NO;
            self.onelineField.delegate = argTarget;
            [self.onelineField becomeFirstResponder];
            [self addSubview:self.onelineField];
        }
        else {
            self.multilineField = [[UITextView alloc] initWithFrame:CGRectMake(10, 10, self.frame.size.width - 20, self.frame.size.height - 209 - 50)];
            self.multilineField.text = argInputString;
            self.multilineField.textColor = [UIColor colorWithRed:0.40 green:0.40 blue:0.40 alpha:1.0];
            self.multilineField.font = [UIFont systemFontOfSize:14];
            self.multilineField.backgroundColor = [UIColor clearColor];
            self.multilineField.textAlignment = NSTextAlignmentLeft;
            self.multilineField.editable = YES;
            self.multilineField.delegate = argTarget;
            NSRange range;
            range.location = 0;
            range.length = 0;
            self.multilineField.selectedRange = range;
            [self.multilineField becomeFirstResponder];
            [self addSubview:self.multilineField];
        }
        
        self.btnAreaView = [[UIView alloc] initWithFrame:CGRectMake(0, self.frame.size.height - 209 - 40, self.frame.size.width, 80)];
        self.btnAreaView.backgroundColor = [UIColor colorWithRed:0.80 green:0.80 blue:0.80 alpha:1.0];

        // エラーメッセージ
        self.errorLabel = [[UILabel alloc] initWithFrame:CGRectMake(10, 13, 200, 14)];
        self.errorLabel.textColor = [UIColor colorWithRed:0.97 green:0.27 blue:0.27 alpha:1.0];
        self.errorLabel.font = [UIFont systemFontOfSize:12];
        self.errorLabel.backgroundColor = [UIColor clearColor];
        self.errorLabel.textAlignment = NSTextAlignmentLeft;
        [self.btnAreaView addSubview:self.errorLabel];
        
        // 保存ボタン
        self.saveBtn = [UIButton buttonWithType:UIButtonTypeCustom];
        self.saveBtn.frame = CGRectMake(0, 0, 100, 40);
        self.saveBtn.backgroundColor = [UIColor clearColor];
        [self.saveBtn setTitleColor:[UIColor whiteColor] forState:UIControlStateNormal];
        [self.saveBtn setTitleColor:[UIColor grayColor] forState:UIControlStateDisabled];
        [self.saveBtn setTitle:NSLocalizedString(@"SAVE", @"保存") forState:UIControlStateNormal];
        [self.saveBtn.titleLabel setFont:[UIFont boldSystemFontOfSize:14]];
        [self.saveBtn sizeToFit];
        self.saveBtn.frame = CGRectMake(self.btnAreaView.frame.size.width - self.saveBtn.frame.size.width - 10, (self.btnAreaView.frame.size.height - self.saveBtn.frame.size.height) / 2.0f - 20, self.saveBtn.frame.size.width, self.saveBtn.frame.size.height);
        self.saveBtn.enabled = NO;
        [self.btnAreaView addSubview:self.saveBtn];
        
        [self addSubview:self.btnAreaView];
    }
    return self;
}

@end
