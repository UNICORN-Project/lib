<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja-JP" xml:lang="ja-JP">
<head>
	<meta http-equiv="Content-Style-Type" content="text/css; charset=UTF-8" />
	<link rel="stylesheet" href="style/pgcommon.css" charset="UTF-8" />

	<title>[SearchTrade]-PGマルチペイメントサービス−モジュールタイプ呼び出しサンプル</title>
</head>
<body>

<div id="header">
	<h1>取引照会/モジュールタイプ(PHP) 呼び出しサンプル</h1>
	<a href="index.html">インデックスに戻る</a>
</div>

<div id="main">

	<?php if( !isset ($_POST['submit']) ){//初期表示です ?>

		<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post">
			<table>
				<tbody>
					<tr>
						<th scope="row">ショップID(ShopID)</th>
						<td><input name="ShopID" type="text" size="27" tabindex="11" /></td>
					</tr>
					<tr>
						<th scope="row">ショップパスワード(ShopPass)</th>
						<td><input name="ShopPass" type="text" size="27" tabindex="11" /></td>
					</tr>
					<tr>
						<th scope="row">オーダーID(OrderID)</th>
						<td><input name="OrderID" type="text" maxlength="27" /></td>
					</tr>
					<tr>
					<th scope="row">決済方法(PayType)</th>
					<td>
						<select name="PayType" tabindex="14">
							<option value="0">0：クレジット </option>
							<option value="1">1：モバイルSuica </option>
							<option value="2">2：モバイルEdy </option>
							<option value="3">3：コンビニ </option>
							<option value="4">4：Pay-easy</option>
							<option value="5">5：Paypal</option>
							<option value="7">7：Webmoney</option>
							<option value="8">8：auかんたん決済</option>
							<option value="9">9：ドコモケータイ払い</option>
							<option value="10">10：ドコモ継続決済</option>
							<option value="11">11：ソフトバンクケータイ支払い決済</option>
							<option value="12">12：じぶん銀行</option>
							<option value="13">13：au継続</option>
							<option value="14">14：JCBプリカ</option>
							<option value="15">15：フレッツ</option>
							<option value="16">16：NET CASH</option>
							<option value="18">18：楽天ID</option>
							<option value="19">19：多通貨クレジットカード</option>
							<option value="20">20：LINE Pay</option>
							<option value="21">21：ネット銀聯</option>
							<option value="22">22：ソフトバンク継続</option>
							<option value="24">24：リクルートかんたん支払い</option>

						</select>
					</td>
				</tr>
				</tbody>
			</table>
			<input name="submit" type="submit" value="実行" />
		</form>

	<?php }else{//送信結果の表示です ?>

		<table>
			<caption>実行結果</caption>
			<tfoot>

			</tfoot>
			<tbody>
				<tr>
					<th scope="row">オーダーID(OrderID)</th>
					<td><?php echo $output->getOrderId() ?></td>
				</tr>
				<tr>
					<th scope="row">ステータス(Status)</th>
					<td><?php echo $output->getStatus() ?></td>
				</tr>
				<tr>
					<th scope="row">処理日時(ProcessDate)</th>
					<td><?php echo $output->getProcessDate() ?></td>
				</tr>
				<tr>
					<th scope="row">処理区分(JobCd)</th>
					<td><?php echo $output->getJobCd() ?></td>
				</tr>
				<tr>
					<th scope="row">取引ID(AccessID)</th>
					<td><?php echo $output->getAccessId() ?></td>
				</tr>
				<tr>
					<th scope="row">取引Pass(AccessPass)</th>
					<td><?php echo $output->getAccessPass() ?></td>
				</tr>
				<tr>
					<th scope="row">商品コード(ItemCode)</th>
					<td><?php echo $output->getItemCode() ?></td>
				</tr>
				<tr>
					<th scope="row">通貨コード(Currency)</th>
					<td><?php echo $output->getCurrency() ?></td>
				</tr>
				<tr>
					<th scope="row">利用金額(Amount)</th>
					<td><?php echo $output->getAmount() ?></td>
				</tr>
				<tr>
					<th scope="row">税送料(Tax)</th>
					<td><?php echo $output->getTax() ?></td>
				</tr>
				<tr>
					<th scope="row">利用金額BigDecimal(AmountBigDecimal)</th>
					<td><?php echo $output->getAmountBigDecimal() ?></td>
				</tr>
				<tr>
					<th scope="row">税送料BigDecimal(TaxBigDecimal)</th>
					<td><?php echo $output->getTaxBigDecimal() ?></td>
				</tr>
				<tr>
					<th scope="row">サイトID(SiteID)</th>
					<td><?php echo $output->getSiteId() ?></td>
				</tr>
				<tr>
					<th scope="row">会員ID(MemberID)</th>
					<td><?php echo $output->getMemberId() ?></td>
				</tr>
				<tr>
					<th scope="row">カード番号(CardNo)</th>
					<td><?php echo $output->getCardNo() ?></td>
				</tr>
				<tr>
					<th scope="row">有効期限(Expire)</th>
					<td><?php echo $output->getExpire() ?></td>
				</tr>
				<tr>
					<th scope="row">支払方法(Method)</th>
					<td><?php echo $output->getMethod() ?></td>
				</tr>
				<tr>
					<th scope="row">支払回数(PayTimes)</th>
					<td><?php echo $output->getPayTimes() ?></td>
				</tr>
				<tr>
					<th scope="row">仕向先カード会社(Forward)</th>
					<td><?php echo $output->getForward() ?></td>
				</tr>
				<tr>
					<th scope="row">トランザクションID(TranID)</th>
					<td><?php echo $output->getTranId() ?></td>
				</tr>
				<tr>
					<th scope="row">承認番号(Approve)</th>
					<td><?php echo $output->getApprovalNo() ?></td>
				</tr>
				<tr>
					<th scope="row">決済方法(PayType)</th>
					<td><?php echo $output->getPayType() ?></td>
				</tr>
				<tr>
					<th scope="row">支払先コンビニ(CvsCode)</th>
					<td><?php echo $output->getCvsCode() ?></td>
				</tr>
				<tr>
					<th scope="row">コンビニ確認番号(CvsConfNo)</th>
					<td><?php echo $output->getCvsConfNo() ?></td>
				</tr>
				<tr>
					<th scope="row">コンビニ受付番号(CvsReceiptNo)</th>
					<td><?php echo $output->getCvsReceiptNo() ?></td>
				</tr>
				<tr>
					<th scope="row">Edy受付番号(EdyReceiptNo)</th>
					<td><?php echo $output->getEdyReceiptNo() ?></td>
				</tr>
				<tr>
					<th scope="row">Edy注文番号(EdyOrderNo)</th>
					<td><?php echo $output->getEdyOrderNo() ?></td>
				</tr>
				<tr>
					<th scope="row">Suica受付番号(SuicaReceiptNo)</th>
					<td><?php echo $output->getSuicaReceiptNO() ?></td>
				</tr>
				<tr>
					<th scope="row">Suica注文番号(SuicaOrderNo)</th>
					<td><?php echo $output->getSuicaOrderNo() ?></td>
				</tr>
				<tr>
					<th scope="row">Pay-easyお客様番号(CustId)</th>
					<td><?php echo $output->getCustId() ?></td>
				</tr>
				<tr>
					<th scope="row">Pay-easy収納機関番号(BkCode)</th>
					<td><?php echo $output->getBkCode() ?></td>
				</tr>
				<tr>
					<th scope="row">Pay-easy確認番号(ConfNo)</th>
					<td><?php echo $output->getConfNo() ?></td>
				</tr>
				<tr>
					<th scope="row">Pay-easy暗号化決済番号(EncryptReceiptNo)</th>
					<td><?php echo $output->getEncryptReceiptNo() ?></td>
				</tr>
				<tr>
					<th scope="row">支払期限日時(PaymentTerm)</th>
					<td><?php echo $output->getPaymentTerm() ?></td>
				</tr>
				<tr>
					<th scope="row">WebMoney管理番号(WebMoneyManagementNo)</th>
					<td><?php echo $output->getWebmoneyManagementNo() ?></td>
				</tr>
				<tr>
					<th scope="row">WebMoney決済コード(WebMoneySettleCode)</th>
					<td><?php echo $output->getWebmoneySettleCode() ?></td>
				</tr>


				<tr>
					<th scope="row">加盟店自由項目１(ClientField1)</th>
					<td><?php echo htmlspecialchars( mb_convert_encoding( $output->getClientField1() , PGCARD_SAMPLE_ENCODING , 'SJIS') ) ?></td>
				</tr>
				<tr>
					<th scope="row">加盟店自由項目２(ClientField2)</th>
					<td><?php echo htmlspecialchars( mb_convert_encoding( $output->getClientField2() , PGCARD_SAMPLE_ENCODING , 'SJIS') ) ?></td>
				</tr>
				<tr>
					<th scope="row">加盟店自由項目３(ClientField3)</th>
					<td><?php echo htmlspecialchars( mb_convert_encoding( $output->getClientField3() , PGCARD_SAMPLE_ENCODING , 'SJIS') ) ?></td>
				</tr>
				<tr>
					<th scope="row">auかんたん決済決済情報番号(AuPayInfoNo)</th>
					<td><?php echo $output->getAuPayInfoNo() ?></td>
				</tr>
				<tr>
					<th scope="row">auかんたん決済支払方法(AuPayMethod)</th>
					<td><?php echo $output->getAuPayMethod() ?></td>
				</tr>
				<tr>
					<th scope="row">auかんたん決済キャンセル金額(AuCancelAmount)</th>
					<td><?php echo $output->getAuCancelAmount() ?></td>
				</tr>
				<tr>
					<th scope="row">auかんたん決済キャンセル税送料(AuCancelTax)</th>
					<td><?php echo $output->getAuCancelTax() ?></td>
				</tr>
				<tr>
					<th scope="row">ドコモ決済番号(DocomoSettlementCode)</th>
					<td><?php echo $output->getDocomoSettlementCode() ?></td>
				</tr>
				<tr>
					<th scope="row">ドコモキャンセル金額(DocomoCancelAmount)</th>
					<td><?php echo $output->getDocomoCancelAmount() ?></td>
				</tr>
				<tr>
					<th scope="row">ドコモキャンセル税送料(DocomoCancelTax)</th>
					<td><?php echo $output->getDocomoCancelTax() ?></td>
				</tr>
				<tr>
					<th scope="row">ソフトバンク処理トラッキングID(SbTrackingId)</th>
					<td><?php echo $output->getSbTrackingId() ?></td>
				</tr>
				<tr>
					<th scope="row">ソフトバンクキャンセル金額(SbCancelAmount)</th>
					<td><?php echo $output->getSbCancelAmount() ?></td>
				</tr>
				<tr>
					<th scope="row">ソフトバンクキャンセル税送料(SbCancelTax)</th>
					<td><?php echo $output->getSbCancelTax() ?></td>
				</tr>
				<tr>
					<th scope="row">じぶん銀行受付番号(JibunReceiptNo)</th>
					<td><?php echo $output->getJibunReceiptNo() ?></td>
				</tr>
				<tr>
					<th scope="row">au継続 初回課金利用金額(FirstAmount)</th>
					<td><?php echo $output->getFirstAmount() ?></td>
				</tr>
				<tr>
					<th scope="row">au継続 初回課金税送料(FirstTax)</th>
					<td><?php echo $output->getFirstTax() ?></td>
				</tr>
				<tr>
					<th scope="row">au継続 課金タイミング区分(AccountTimingKbn)</th>
					<td><?php echo $output->getAccountTimingKbn() ?></td>
				</tr>
				<tr>
					<th scope="row">au継続 課金タイミング(AccountTiming)</th>
					<td><?php echo $output->getAccountTiming() ?></td>
				</tr>
				<tr>
					<th scope="row">au継続 初回課金日(FirstAccountDate)</th>
					<td><?php echo $output->getFirstAccountDate() ?></td>
				</tr>
				<tr>
					<th scope="row">au継続 auエラーコード(AuContinuanceErrCode)</th>
					<td><?php echo $output->getAuContinuanceErrCode() ?></td>
				</tr>
				<tr>
					<th scope="row">au継続 auエラー詳細(AuContinuanceErrInfo)</th>
					<td><?php echo $output->getAuContinuanceErrInfo() ?></td>
				</tr>
				<tr>
					<th scope="row">au継続 au継続課金ID(AuContinueAccountId)</th>
					<td><?php echo $output->getAuContinueAccountId() ?></td>
				</tr>
				<tr>
					<th scope="row">JcbPreca 伝票番号(JcbPrecaSalesCode)</th>
					<td><?php echo $output->getJcbPrecaSalesCode() ?></td>
				</tr>
				<tr>
					<th scope="row">Flets 処理依頼通番(RequestNo)</th>
					<td><?php echo $output->getRequestNo() ?></td>
				</tr>
				<tr>
					<th scope="row">Flets 処理受付番号(AccountNo)</th>
					<td><?php echo $output->getAccountNo() ?></td>
				</tr>
				<tr>
					<th scope="row">Flets センターコード(CenterCode)</th>
					<td><?php echo $output->getCenterCode() ?></td>
				</tr>
				<tr>
					<th scope="row">Flets 変更前利用金額(PreviousAmount)</th>
					<td><?php echo $output->getPreviousAmount() ?></td>
				</tr>
				<tr>
					<th scope="row">Flets 変更前税送料(PreviousTax)</th>
					<td><?php echo $output->getPreviousTax() ?></td>
				</tr>
				<tr>
					<th scope="row">Netcash NET CASH決済方法(NetCashPayType)</th>
					<td><?php echo $output->getNetCashPayType() ?></td>
				</tr>
				<tr>
					<th scope="row">RakutenId 注文日(OrderDate)</th>
					<td><?php echo $output->getOrderDate() ?></td>
				</tr>
				<tr>
					<th scope="row">RakutenId 完了日(CompletionDate)</th>
					<td><?php echo $output->getCompletionDate() ?></td>
				</tr>
				<tr>
					<th scope="row">RakutenId クーポン金額(RakutenidCouponFee)</th>
					<td><?php echo $output->getRakutenidCouponFee() ?></td>
				</tr>
				<tr>
					<th scope="row">Linepay LINE Pay商品名(LinepayProductName)</th>
					<td><?php echo htmlspecialchars( mb_convert_encoding( $output->getLinepayProductName() , PGCARD_SAMPLE_ENCODING , 'SJIS') ) ?></td>
				</tr>
				<tr>
					<th scope="row">Linepay LINE Payキャンセル金額(LinepayCancelAmount)</th>
					<td><?php echo $output->getLinepayCancelAmount() ?></td>
				</tr>
				<tr>
					<th scope="row">Linepay LINE Payキャンセル税送料(LinepayCancelTax)</th>
					<td><?php echo $output->getLinepayCancelTax() ?></td>
				</tr>
				<tr>
					<th scope="row">Linepay LINE Pay支払手段(LinepayPayMethod)</th>
					<td><?php echo $output->getLinepayPayMethod() ?></td>
				</tr>
				<tr>
					<th scope="row">Unionpay 商品名(CommodityName)</th>
					<td><?php echo $output->getCommodityName() ?></td>
				</tr>
				<tr>
					<th scope="row">SbContinuance 課金開始月(SbStartChargeMonth)</th>
					<td><?php echo $output->getSbStartChargeMonth() ?></td>
				</tr>
				<tr>
					<th scope="row">Recruit 注文番号(RcOrderId)</th>
					<td><?php echo $output->getRcOrderId() ?></td>
				</tr>
				<tr>
					<th scope="row">Recruit 顧客IDハッシュ値(RcCustomerId)</th>
					<td><?php echo $output->getRcCustomerId() ?></td>
				</tr>
				<tr>
					<th scope="row">Recruit 注文時刻(RcOrderTime)</th>
					<td><?php echo $output->getRcOrderTime() ?></td>
				</tr>
				<tr>
					<th scope="row">Recruit 行使ポイント数(RcUsePoint)</th>
					<td><?php echo $output->getRcUsePoint() ?></td>
				</tr>
				<tr>
					<th scope="row">Recruit リクルート原資クーポン割引額(RcUseCoupon)</th>
					<td><?php echo $output->getRcUseCoupon() ?></td>
				</tr>
				<tr>
					<th scope="row">Recruit 加盟店様原資クーポン割引額(RcUseShopCoupon)</th>
					<td><?php echo $output->getRcUseShopCoupon() ?></td>
				</tr>
				<tr>
					<th scope="row">Recruit オーソリ期限延長実施日(RcUpdateAuthDay)</th>
					<td><?php echo $output->getRcUpdateAuthDay() ?></td>
				</tr>

			</tbody>
		</table>
	<?php }//if( !isset ($_POST['submit']) ) ?>
</div>

<div id="footer">
	<em>Copyright (c) 2008 GMO Payment Gateway,Inc. All Rights Reserved.</em>
</div>

</body>
</html>
