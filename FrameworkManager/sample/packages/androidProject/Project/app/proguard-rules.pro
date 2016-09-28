# Add project specific ProGuard rules here.
# By default, the flags in this file are appended to flags specified
# in /Users/c1363/Documents/android-sdk/tools/proguard/proguard-android.txt
# You can edit the include path and order by changing the proguardFiles
# directive in build.gradle.
#
# For more details, see
#   http://developer.android.com/guide/developing/tools/proguard.html

# Add any project specific keep options here:

# If your project uses WebView with JS, uncomment the following
# and specify the fully qualified class name to the JavaScript interface
# class:
#-keepclassmembers class fqcn.of.javascript.interface.for.webview {
#   public *;
#}

-optimizationpasses 5
-dontusemixedcaseclassnames
-dontskipnonpubliclibraryclasses
-dontpreverify
-verbose
-optimizations !code/simplification/arithmetic,!field/*,!class/merging/*

-keep public class * extends android.app.Activity
-keep public class * extends android.app.Application
-keep public class * extends android.app.Service
-keep public class * extends android.content.BroadcastReceiver
-keep public class * extends android.content.ContentProvider
-keep public class * extends android.app.backup.BackupAgentHelper
-keep public class * extends android.preference.Preference
-keep public class * extends android.widget.Scroller
-keep public class * extends android.widget.ScrollView
-keep public class * extends android.widget.OverScroller
-keep public class com.android.vending.licensing.ILicensingService
-keep public class android.app.INotificationManager { *; }
-keep public class android.app.ITransientNotification { *; }

-dontwarn android.support.v4.**
-dontwarn org.apache.commons.**
-keep class org.apache.http.** { *; }
-dontwarn org.apache.http.**
-keep class com.google.android.gms.** { *; }
-dontwarn com.google.android.gms.**

-keep class android.support.** { *; }
-keep,allowshrinking public class com.android.vending.billing.*
-keepclasseswithmembernames class * {
    native <methods>;
}

-keepclasseswithmembers class * {
    public <init>(android.content.Context, android.util.AttributeSet);
}

-keepclasseswithmembers class * {
    public <init>(android.content.Context, android.util.AttributeSet, int);
}

-keepclassmembers class * extends android.app.Activity {
   public void *(android.view.View);
}

-keepclassmembers enum * {
    public static **[] values();
    public static ** valueOf(java.lang.String);
}

-keep class * implements android.os.Parcelable {
  public static final android.os.Parcelable$Creator *;
}

-keep public class it.partytrack.sdk.ReferrerReceiver{
    public protected *;
}
-keep public class it.partytrack.sdk.MultipleReferrerReceiver{
    public protected *;
}

