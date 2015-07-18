//
//  MTextFieldView.h
//  GMatch
//
//  Created by saimushi on 2014/10/03.
//
@interface MTextFieldView : UIView
{
    UIView *bgView;
    UITextField *onelineField;
    UITextView *multilineField;
    UIButton *saveBtn;
    UILabel *errorLabel;
    UIView *btnAreaView;
}

@property (strong, nonatomic) UIView *bgView;
@property (strong, nonatomic) UITextField *onelineField;
@property (strong, nonatomic) UITextView *multilineField;
@property (strong, nonatomic) UIButton *saveBtn;
@property (strong, nonatomic) UILabel *errorLabel;
@property (strong, nonatomic) UIView *btnAreaView;

- (id)initWithFrame:(CGRect)argFrame :(NSString *)argInputString :(int)argNumberOfLine :(id)argTarget;

@end
