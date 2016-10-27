# UNICORN-ProjectのAndorid雛形サンプルについて

## プロジェクトの開き方

AndroidStudio2.xを起動して  
「Welcom to Android Studio」->「Open an existing Android Studio project」->「Open File or Project」または  
「File」->「Open...」->「Open File or Project」で  
このファイルと並列にある「Project」フォルダを指定して下さい。

## 実行に関する注意事項

- UnicornではGenymotionでのエミュレーター実行を推奨しています。

###### 開発環境がMacの場合、以下の手順でGenymotion環境の構築が出来ます。

```
[xcodeはインストールされている前提]
xcode-select --install
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
brew tap caskroom/cask
brew install caskroom/cask/brew-cask
brew cask install virtualbox
brew cask install genymotion
```

- エミュレータからローカル開発環境へ通信する場合、エミュレータのhostsファイルを書換える必要があります。

###### Genymotion環境下でのエミュレータのhostsファイルの書換方法は以下になります。

```
/Applications/Genymotion.app/Contents/MacOS/tools/adb root
/Applications/Genymotion.app/Contents/MacOS/tools/adb remount
/Applications/Genymotion.app/Contents/MacOS/tools/adb push /etc/hosts /system/etc
```

###### ※ adbのパスさせあっていれば、AndroidStudioのエミュレータでも同一手順で設定出来ます。