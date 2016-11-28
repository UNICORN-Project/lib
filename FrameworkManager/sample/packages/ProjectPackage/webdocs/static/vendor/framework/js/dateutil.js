var dateUtil = {
	dateWithFormat : function (format, date, GMT) {
		if (!format) {
			format = '%y-%m-%d %h:%i:%s';
		}
		if (typeof date === 'number') {
			date = new Date(date);
		}
		else if (typeof date === 'string') {
			date = new Date(date.replace(/-/g, '/'));
		}
		else if (!(date instanceof Date)) {
			date = new Date(Date.nowDate());
		}
		var zeroFill = function (number, digit) {
			return ('00' + number).slice(digit * -1);
		};
		var
			year = date.getFullYear(),
			month = zeroFill(date.getMonth() + 1, 2),
			date_n = zeroFill(date.getDate(), 2),
			hour = zeroFill(date.getHours(), 2),
			minute = zeroFill(date.getMinutes(), 2),
			second = zeroFill(date.getSeconds(), 2),
			milli_second = zeroFill(date.getMilliseconds(), 3)
		;
		if (GMT){
			// GMTに変換
			date = new Date(date.valueOf() + date.getTimezoneOffset() * 60000);
			year = date.getFullYear(),
			month = zeroFill(date.getMonth() + 1, 2),
			date_n = zeroFill(date.getDate(), 2),
			hour = zeroFill(date.getHours(), 2),
			minute = zeroFill(date.getMinutes(), 2),
			second = zeroFill(date.getSeconds(), 2),
			milli_second = zeroFill(date.getMilliseconds(), 3)
		}
		return format.replace(/(%*)%([ymdhisu])/g, function (a, escape_str, type) {
			if (escape_str.length % 2 === 0) {
				switch (type) {
					case 'y':
						type = year;
						break;
					case 'm':
						type = month;
						break;
					case 'd':
						type = date_n;
						break;
					case 'h':
						type = hour;
						break;
					case 'i':
						type = minute;
						break;
					case 's':
						type = second;
						break;
					case 'u':
						type = milli_second;
						break;
					default:
						return;
				}
			}
			return escape_str.replace(/%%/g, '%') + type;
		});
	},
	convertToLocale : function(gmtStr) {
		console.log(gmtStr);
		// 「2015-02-20 07:41:28 GMT」の場合「2015/02/20 16:41:28 JST」に変換
		var f = gmtStr.replace(/-/g, "/");
		var time = Date.parse(f);
		var min = new Date().getTimezoneOffset() * 1000 * 60;
		var m = time - min;
		var date = new Date(time - min);
		console.log(date);
		console.log(date.toLocaleString());
		return date.toLocaleString();
	},
};
