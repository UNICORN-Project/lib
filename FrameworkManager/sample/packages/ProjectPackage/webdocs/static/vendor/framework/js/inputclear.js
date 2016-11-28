$(function() {
	$('.inputclear').off('click').on('click', function(){
		var _prev = $(this).prev();
		if (typeof _prev != 'undefined' && true == _prev.hasClass('inputtext')) {
			_prev.val(null);
			_prev.focus();
			_prev.blur();
		}
		var _next = $(this).next();
		var removeErrorMessage = false;
		if (typeof _next != 'undefined' && true == _next.hasClass('error')) {
			_next.remove();
			removeErrorMessage = true;
		}
		var _parent = $(this).parent();
		if (true === removeErrorMessage && typeof _parent != 'undefined' && true == _parent.hasClass('input') && true == _parent.hasClass('haserror')){
			_parent.removeClass('haserror');
		}
	});
});
