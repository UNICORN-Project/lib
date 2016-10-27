package jp.co.project.activity;

import android.content.Context;
import android.content.Intent;
import android.graphics.PixelFormat;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.support.v4.app.Fragment;
import android.support.v4.app.FragmentActivity;
import android.support.v4.app.FragmentManager;
import android.support.v4.app.FragmentTransaction;
import android.view.Gravity;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewTreeObserver;
import android.view.Window;
import android.view.WindowManager;
import android.view.animation.Animation;
import android.view.animation.AnimationUtils;
import android.widget.FrameLayout;
import android.widget.ImageButton;
import android.widget.LinearLayout;
import android.widget.RelativeLayout;

import com.unicorn.app.App;
import com.unicorn.handler.ModelHandler;
import com.unicorn.manager.DataManager;
import com.unicorn.utilities.Log;
import com.unicorn.utilities.Utilitis;

import java.util.Map;

import jp.co.project.R;
import jp.co.project.constant.Constant;
import jp.co.project.fragment.BaseFragment;
import jp.co.project.fragment.TimelineFragment;
import jp.co.project.fragment.MyTimelineFragment;
import jp.co.project.fragment.SettingFragment;
import jp.co.project.model.ProfileModel;
import jp.co.project.util.ViewUtils;

public class MainActivity extends FragmentActivity {

	private RelativeLayout root;
	private LinearLayout llFooter;
	private ImageButton btnFragment1;
	private ImageButton btnFragment2;
	private ImageButton btnFragment3;

	private Fragment currentFragment;
	private int width = 0;
	private int height = 0;

	private boolean isFirst = false;
	private String path;

	private TimelineFragment tab1Fragment;
	private MyTimelineFragment tab2Fragment;
	private SettingFragment tab3Fragment;

	public boolean isChangingFragment = false;

	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);

		getWindow().requestFeature(Window.FEATURE_ACTION_BAR);
		setContentView(R.layout.activity_main);

		root = (RelativeLayout) findViewById(R.id.root);
		llFooter = (LinearLayout) findViewById(R.id.ll_footer);
		btnFragment1 = (ImageButton) findViewById(R.id.btn_fragment1);
		btnFragment2 = (ImageButton) findViewById(R.id.btn_fragment2);
		btnFragment3 = (ImageButton) findViewById(R.id.btn_fragment3);

		// フッター：tab1ボタン
		btnFragment1.setOnClickListener(new View.OnClickListener() {
			public void onClick(View v) {

				if(isChangingFragment) {
					return;
				}

				FragmentManager fm = getSupportFragmentManager();
				if (tab1Fragment == null) {
					tab1Fragment = new TimelineFragment();
					// 現在ページがtab1の場合はスクロールを戻す
				} else if (fm.findFragmentByTag(tab1Fragment.getClass().getSimpleName()) != null) {
					// 現在ページがtab1の場合はスクロールを戻す
					if (currentFragment instanceof TimelineFragment || fm.getBackStackEntryCount() == 1) {

						// 現在ページがtab1より先の場合はtab1に戻す
					} else {
						fm.popBackStack(tab1Fragment.getClass().getSimpleName(), 0);
						currentFragment = tab1Fragment;
					}
					return;
				}
				isChangingFragment = true;
				setFooterTab1Active();
				startFragment(tab1Fragment);
			}
		});

		// フッター：tab2ボタン
		btnFragment2.setOnClickListener(new View.OnClickListener() {
			public void onClick(View v) {

				if(isChangingFragment) {
					return;
				}

				FragmentManager fm = getSupportFragmentManager();
				if (tab2Fragment == null) {
					tab2Fragment = new MyTimelineFragment();
					// 現在ページがtab2の場合はスクロールを戻す
				} else if (fm.findFragmentByTag(tab2Fragment.getClass().getSimpleName()) != null) {
					if (currentFragment instanceof MyTimelineFragment || fm.getBackStackEntryCount() == 1) {

						// 現在ページがtab2より先の場合はtab2に戻す
					} else {
						fm.popBackStack(tab2Fragment.getClass().getSimpleName(), 0);
						currentFragment = tab2Fragment;
					}
					return;
				}
				isChangingFragment = true;
				setFooterTab2Active();
				startFragment(tab2Fragment);
			}
		});

		// フッター：tab3ボタン
		btnFragment3.setOnClickListener(new View.OnClickListener() {
			public void onClick(View v) {

				if(isChangingFragment) {
					return;
				}

				FragmentManager fm = getSupportFragmentManager();
				if (tab3Fragment == null) {
					tab3Fragment = new SettingFragment();
					// 現在ページがtab3の場合はスクロールを戻す
				} else if (fm.findFragmentByTag(tab3Fragment.getClass().getSimpleName()) != null) {
					// 現在ページがtab3の場合はスクロールを戻す
					if (currentFragment instanceof SettingFragment || fm.getBackStackEntryCount() == 1) {

						// 現在ページがtab3より先の場合はtab3に戻す
					} else {
						fm.popBackStack(tab3Fragment.getClass().getSimpleName(), 0);
						currentFragment = tab3Fragment;
					}
					return;
				}
				isChangingFragment = true;
				setFooterTab3Active();
				startFragment(tab3Fragment);
			}
		});

		App.activity = this;

		final Intent intent = getIntent();
		if (intent != null && Intent.ACTION_VIEW.equals(intent.getAction())) {
			Uri uri = intent.getData();
			if (uri != null) {
				path = uri.getSchemeSpecificPart();
			}
		}

		final ModelHandler completeHandler = new ModelHandler(this) {
			@Override
			public void success() {
				if(DataManager.getInstance().myModel.ID == null){
					final ModelHandler completeHandler = new ModelHandler(MainActivity.this) {
						@Override
						public void success() {
							//新規登録
							DataManager.getInstance().myModel.setName("名無しー");
							DataManager.getInstance().myModel.ID=DataManager.getInstance().getUserModel().ID;
							final ModelHandler completeHandler2 = new ModelHandler(MainActivity.this) {
								@Override
								public void success() {
									Log.d("success","success");
								}

								@Override
								public void disconnect() {
									Log.d("disconnect","disconnect");
								}

								@Override
								public void failure() {
									Log.d("failure","failure");
								}
							};
							DataManager.getInstance().myModel.save(completeHandler2);
						}

						@Override
						public void disconnect() {
							Log.d("failure","failure");
						}

						@Override
						public void failure() {
							Log.d("failure","failure");
						}
					};
					DataManager.getInstance().loadUserModel(completeHandler);

				}
				showSplash(intent);
			}

			@Override
			public void disconnect() {
				DataManager.getInstance().load(this);
			}

			@Override
			public void failure() {

			}
		};
		DataManager.getInstance().load(completeHandler);

	}

	/**
	 * トップ系の画面をクリアします。
	 */
	public void clearTopFragment() {

		tab1Fragment = null;
		tab2Fragment = null;
		tab3Fragment = null;
	}

	/**
	 * 指定したFragmentに移動します。
	 *
	 * @param fragment
	 * @param isClear
	 * @param animType 1:通常遷移アニメーション
	 */
	public void nextFragment(BaseFragment fragment, boolean isClear, int animType, FragmentManager.OnBackStackChangedListener listener) {
		FragmentManager fm = getSupportFragmentManager();
		FragmentTransaction ft = fm.beginTransaction();
		if (isClear && fm.getBackStackEntryCount() > 0) {
			fm.popBackStack(fm.getBackStackEntryAt(0).getName(), FragmentManager.POP_BACK_STACK_INCLUSIVE);
		}
		if (animType == 1) {
			ft.setCustomAnimations(R.anim.right_in, R.anim.left_out, R.anim.left_in, R.anim.right_out);
		} else if (animType == 2) {
			ft.setCustomAnimations(R.anim.bottom_in, R.anim.left_out, R.anim.left_in, R.anim.bottom_out);
		} else if (animType == 3) {
			ft.setCustomAnimations(R.anim.left_in, R.anim.right_out, R.anim.right_in, R.anim.left_out);
		} else if (animType == 4) {
			ft.setCustomAnimations(R.anim.bottom_in, R.anim.top_out, R.anim.top_in, R.anim.bottom_out);
		}
		ft.replace(R.id.ll_main, fragment, fragment.getClass().getSimpleName());
		ft.addToBackStack(fragment.getClass().getSimpleName());
		currentFragment = fragment;
		ft.commitAllowingStateLoss();
	}

	private synchronized void start(String type) {
		setFooterVisibility(View.VISIBLE);
		setFooterTab1Active();
		startTab1Fragment();
	}

	/**
	 * 指定したFragmentを開始します。
	 *
	 * @param fragment
	 */
	public void startFragment(BaseFragment fragment) {
		nextFragment(fragment, true, 0, null);
	}

	/**
	 * 指定したFragmentに移動します。
	 *
	 * @param fragment
	 */
	public void nextFragment(BaseFragment fragment, int animType) {
		nextFragment(fragment, false, animType, null);
	}

	/**
	 * 指定したFragmentに移動します。
	 *
	 * @param fragment
	 */
	public void nextFragment(BaseFragment fragment) {
		nextFragment(fragment, false, 1, null);
	}

	/**
	 * 指定したFragmentに移動します。
	 *
	 * @param fragment
	 * @param listener
	 */
	public void nextFragment(BaseFragment fragment, FragmentManager.OnBackStackChangedListener listener) {
		nextFragment(fragment, false, 1, listener);
	}

	/**
	 * Fragmentを一つ前に戻します。
	 */
	public void backFragment() {
		backFragment(false);
	}

	/**
	 * Fragmentを一つ前に戻します。
	 */
	public void backFragment(boolean immediate) {

		FragmentManager fm = getSupportFragmentManager();
		if (fm.getBackStackEntryCount() > 0) {
			if (immediate) {
				fm.popBackStackImmediate();
			} else {
				fm.popBackStack();
			}

		}
	}

	/**
	 * 指定したFragmentに戻します。
	 */
	public void backFragment(String name, Map<String, Object> map) {
		FragmentManager fm = getSupportFragmentManager();
		if (map != null) {
			BaseFragment fragment = (BaseFragment) fm.findFragmentByTag(name);
			fragment.setBackArgsMap(map);
		}
		if (fm.getBackStackEntryCount() > 0) {
			fm.popBackStack(name, 0);
		}
	}

	/**
	 * フッターの表示を制御します。
	 *
	 * @param visibility View.GONE or View.VISIBLE or View.INVISIBLE
	 */
	public void setFooterVisibility(final int visibility) {
		llFooter.setVisibility(visibility);
	}

	/**
	 * サイズを保持します。
	 */
	public void setSize() {

		// UI表示されてから縦横サイズを取得する
		ViewTreeObserver vto = root.getViewTreeObserver();
		vto.addOnGlobalLayoutListener(new ViewTreeObserver.OnGlobalLayoutListener() {
			@SuppressWarnings("deprecation")
			@Override
			public void onGlobalLayout() {
				setWidthHeight();
				ViewUtils.removeGlobalOnLayoutListener(root.getViewTreeObserver(), this);
			}
		});
	}

	public void setWidthHeight() {
		if (width == 0 || height == 0) {
			width = root.getWidth();
			height = root.getHeight();
		}
	}

	/**
	 * Activityのルートレイアウトを取得します。
	 *
	 * @return
	 */
	public RelativeLayout getRootLayout() {
		return root;
	}

	/**
	 * アプリの表示領域（横幅）サイズを取得します。
	 *
	 * @return
	 */
	public int getFullWidth() {
		return width;
	}

	/**
	 * アプリの表示領域（縦幅）サイズを取得します。
	 *
	 * @return
	 */
	public int getFullHeight() {
		return height;
	}

	/**
	 * Fragmentの表示領域（横幅）サイズを取得します。
	 *
	 * @return
	 */
	public int getWidth() {
		return root.getWidth();
	}

	/**
	 * Fragmentの表示領域（縦幅）サイズを取得します。
	 *
	 * @return
	 */
	public int getHeight() {
		return root.getHeight();
	}

	public Fragment getCurrentFragment() {
		return currentFragment;
	}


	@Override
	public void onStart() {
		super.onStart();

	}

	@Override
	public void onResume() {
		super.onResume();
	}

	public void showSplash(final Intent intent) {

		// プッシュ通知からか、URLスキームからの場合はスルー
		if (Utilitis.isNotEmpty(intent.getType()) || Utilitis.isNotEmpty(intent.getType())) {
			start(intent.getType());
			return;
		}

		LayoutInflater layoutInflater = LayoutInflater.from(getApplicationContext());
		// 重ね合わせするViewの設定を行う
		WindowManager.LayoutParams params;
		if(Build.VERSION.SDK_INT >= 19){
			params = new WindowManager.LayoutParams(
					WindowManager.LayoutParams.MATCH_PARENT,
					WindowManager.LayoutParams.MATCH_PARENT,
					WindowManager.LayoutParams.TYPE_TOAST,
					WindowManager.LayoutParams.FLAG_WATCH_OUTSIDE_TOUCH |
							WindowManager.LayoutParams.FLAG_NOT_TOUCH_MODAL |
							WindowManager.LayoutParams.FLAG_NOT_FOCUSABLE,
					PixelFormat.TRANSLUCENT);
		}else {
			params = new WindowManager.LayoutParams(
					WindowManager.LayoutParams.MATCH_PARENT,
					WindowManager.LayoutParams.MATCH_PARENT,
					WindowManager.LayoutParams.TYPE_SYSTEM_ALERT,
					WindowManager.LayoutParams.FLAG_WATCH_OUTSIDE_TOUCH |
							WindowManager.LayoutParams.FLAG_NOT_TOUCH_MODAL |
							WindowManager.LayoutParams.FLAG_NOT_FOCUSABLE,
					PixelFormat.TRANSLUCENT);
		}
		params.gravity = Gravity.TOP;
		final WindowManager wm = (WindowManager) getSystemService(Context.WINDOW_SERVICE);
		// レイアウトファイルから重ね合わせするViewを作成する
		final View splash = layoutInflater.inflate(R.layout.layout_splash, null);
		final FrameLayout flSplash = (FrameLayout) splash.findViewById(R.id.fl_splash);

		Animation animation = AnimationUtils.loadAnimation(getApplicationContext(), R.anim.splash);
		animation.setAnimationListener(new Animation.AnimationListener() {
			@Override
			public void onAnimationStart(Animation animation) {
			}

			@Override
			public void onAnimationRepeat(Animation animation) {
			}

			@Override
			public void onAnimationEnd(Animation animation) {
				new Handler().post(new Runnable() {
					@Override
					public void run() {
						wm.removeViewImmediate(splash);
					}
				});
				// アニメーション終了時
				// 初回起動ではなくて、プッシュ通知からではなくて、URLスキームからではない場合
				if (!isFirst && Utilitis.isEmpty(intent.getType()) && Utilitis.isEmpty(intent.getType())) {
					start(intent.getType());
				}
			}
		});

		if (flSplash != null) {
			// Viewを画面上に重ね合わせする
			wm.addView(splash, params);
			flSplash.startAnimation(animation);
		} else {
			start(intent.getType());
		}
	}

	@Override
	public void onPause() {
		super.onPause();
	}

	/**
	 * アプリ起動時のプッシュ通知を受け取る為に必要
	 */
	@Override
	public void onNewIntent(Intent intent) {
		super.onNewIntent(intent);
		setIntent(intent);
		// プッシュ通知からか、URLスキームからの場合はスルー
		if (Utilitis.isNotEmpty(intent.getType()) || Utilitis.isNotEmpty(intent.getStringExtra("logpush"))) {
			start(intent.getType());
			return;
		}
	}

	@Override
	public void onBackPressed(){
		FragmentManager fm = getSupportFragmentManager();
		if (fm.getBackStackEntryCount() > 1) {
			super.onBackPressed();
		}else{
			finish();
		}
	}

	public void setFooterTab1Active() {
		setFooterTab1(true);
		setFooterTab2(false);
		setFooterTab3(false);
	}

	public void setFooterTab2Active() {
		setFooterTab1(false);
		setFooterTab2(true);
		setFooterTab3(false);
	}

	public void setFooterTab3Active() {
		setFooterTab1(false);
		setFooterTab2(false);
		setFooterTab3(true);
	}

	public void setFooterAllNonActive() {
		setFooterTab1(false);
		setFooterTab2(false);
		setFooterTab3(false);
	}

	private void setFooterTab1(boolean isActive) {
		if (isActive) {
			btnFragment1.setBackgroundResource(R.drawable.tab_on);
			btnFragment1.setTag(Constant.FLG_ON);
		} else {
			btnFragment1.setBackgroundResource(R.drawable.tab_off);
			btnFragment1.setTag(Constant.FLG_OFF);
		}
	}

	private void setFooterTab2(boolean isActive) {
		if (isActive) {
			btnFragment2.setBackgroundResource(R.drawable.tab_on);
			btnFragment2.setTag(Constant.FLG_ON);
		} else {
			btnFragment2.setBackgroundResource(R.drawable.tab_off);
			btnFragment2.setTag(Constant.FLG_OFF);
		}
	}

	private void setFooterTab3(boolean isActive) {
		if (isActive) {
			btnFragment3.setBackgroundResource(R.drawable.tab_on);
			btnFragment3.setTag(Constant.FLG_ON);
		} else {
			btnFragment3.setBackgroundResource(R.drawable.tab_off);
			btnFragment3.setTag(Constant.FLG_OFF);
		}
	}

	/**
	 * Tab1Fragmentを開始します。
	 */
	public void startTab1Fragment() {
		tab1Fragment = new TimelineFragment();
		setFooterTab1Active();
		startFragment(tab1Fragment);
	}

	/**
	 * Tab2Fragmentを開始します。
	 */
	public void startTab2Fragment() {
		tab2Fragment = new MyTimelineFragment();
		setFooterTab2Active();
		startFragment(tab2Fragment);
	}

	/**
	 * Tab3Fragmentを開始します。
	 */
	public void startTab3Fragment() {
		tab3Fragment = new SettingFragment();
		setFooterTab3Active();
		startFragment(tab3Fragment);
	}

	@Override
	protected void onActivityResult(int requestCode, int resultCode, Intent data) {
		super.onActivityResult(requestCode, resultCode, data);

	}
}
