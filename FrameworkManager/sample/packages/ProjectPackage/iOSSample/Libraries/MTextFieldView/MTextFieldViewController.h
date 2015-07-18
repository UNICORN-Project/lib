//
//  MTextFieldViewController.h
//
//  Created by saimushi on 2014/09/19.
//
@class MTextFieldView;

@interface MTextFieldViewController : UIViewController <UITextFieldDelegate, UITextViewDelegate>
{
    MTextFieldView *inputView;
}

@property (strong, nonatomic) MTextFieldView *inputView;

+ (MTextFieldViewController *)show:(BOOL)argPush :(UIViewController *)argTarget :(NSString *)argTitle :(NSString *)argInputString :(int)argNumberOfLine :(int)argMinLength :(int)argMaxLength :(BOOL)argSecure :(UIKeyboardType)argKeyboardType :(void(^)(NSString *argInputText, MTextFieldViewController *argVC))argCompletion;
- (void)onPushBackButton:(id)sender;

@end
