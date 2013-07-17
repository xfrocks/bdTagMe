/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined) {
	XenForo.bdTagMe_ProfilePostAutoComplete = function($textarea) {
		// copied from XenForo.AutoComplete.__construct
		
		// sometimes our selector will overlap so we need to check first
		var existing = $textarea.data('bdTagMe_ProfilePostAutoComplete');
		if (existing) return;
		$textarea.data('bdTagMe_ProfilePostAutoComplete', this);

		this.$input = $textarea;
		this.textarea = $textarea[0];
		this.url = 'index.php?members/bdtagme-find&_xfResponseType=json';

		var options = {
			multiple: false,
			minLength: 2, // min word length before lookup
			queryKey: 'q',
			extraParams: {},
			jsonContainer: 'results',
			autoSubmit: false
		};
		
		this.multiple = options.multiple;
		this.minLength = options.minLength;
		this.queryKey = options.queryKey;
		this.extraParams = options.extraParams;
		this.jsonContainer = options.jsonContainer;
		this.autoSubmit = options.autoSubmit;
		
		// PLEASE UPDATE THE SYMBOL AND REGEX IF YOU CHANGE IT HERE. THE 3 PLACES ARE:
		// xenforo/js/bdTagMe/full/frontend.js
		// xenforo/js/bdTagMe/full/tinymce_plugin.js
		// xenforo/library/bdTagMe/Engine.php
		this.symbol = '@';
		this.regex = new RegExp(/[\s\(\)\.,!\?:;@\\\\{}'"]/);
		
		this.suggestionMaxLength = 0;
		if (XenForo.bdTagMe_suggestionMaxLength) {
			this.suggestionMaxLength = XenForo.bdTagMe_suggestionMaxLength;
		}

		this.loadVal = '';
		this.results = new XenForo.AutoCompleteResults({
			onInsert: $.context(this, 'addValue')
		});
		
		// unbind previously binded listener
		$textarea.unbind('keydown');
		
		$textarea.attr('autocomplete', 'off')
			.keydown($.context(this, 'keystroke2'))
			.keypress($.context(this, 'operaKeyPress'))
			.blur($.context(this, 'blur'));

		$textarea.closest('form').submit($.context(this, 'hideResults'));
	};
	XenForo.bdTagMe_ProfilePostAutoComplete.prototype = $.extend(true, {}, XenForo.AutoComplete.prototype);
	XenForo.bdTagMe_ProfilePostAutoComplete.prototype.keystroke2 = function(e) {
		var code = e.keyCode || e.charCode;
		var resultsVisible = this.results.isVisible();

		switch(code)
		{
			case 40: // down
			case 38: // up
			case 27: // esc
				if (!resultsVisible) {
					// if our results is not visible
					// stop calling the keystroke method
					// or user won't be able to navigate around
					// XenForo should fix this...
					return false;;
				}
		}
		
		var result = this.keystroke(e);
		
		if (code == 13 && !resultsVisible) {
			if (this.$input.hasClass('StatusEditor')) {
				// XenForo.StatusEditor.prototype.preventNewLine
				// we have to do this because we unbind'd it earlier
				e.preventDefault();

				$(this.$input.get(0).form).submit();

				return false;
			}
		}
		
		return result;
	};
	XenForo.bdTagMe_ProfilePostAutoComplete.prototype.getPartialValue = function() {
		return this.val();
	};
	XenForo.bdTagMe_ProfilePostAutoComplete.prototype.addValue = function(value) {
		return this.val(value);
	};
	XenForo.bdTagMe_ProfilePostAutoComplete.prototype.val = function(newValue) {
		// I hate IE
		if (typeof this.textarea.selectionStart == 'undefined') return '';
		
		var fullText = this.$input.val();
		var startOffset = this.textarea.selectionStart;
		var text = fullText;
		var value = '';
		
		if (fullText.length > startOffset) {
			// ignore the text after the cursor
			text = fullText.substr(0, startOffset);
		}
		
		// get the text after the last symbol
		var lastIndexOfSymbol = text.lastIndexOf(this.symbol);
		var tmp = text.substr(lastIndexOfSymbol + 1);
		var valueFound = false;
		
		if (lastIndexOfSymbol > -1) {
			if (this.suggestionMaxLength > 0) {
				// there is maximum length, checks for it
				if (text.length - lastIndexOfSymbol < this.suggestionMaxLength) {
					valueFound = true;
				}
			} else {
				// no maximum length, checks by regex
				if (this.regex.test(tmp) == false) {
					valueFound = true;
				}
			}
		}
		
		// check to make sure there no spaces in front of our text
		// since 1.5.5
		if (valueFound) {
			if (tmp != tmp.replace(/^\s+/, '')) {
				valueFound = false;
			}
		}
		
		if (valueFound) {
			// something has been found!
			value = tmp;
			
			if (typeof newValue != 'undefined') {
				var newText = text.substr(0, lastIndexOfSymbol + 1) + newValue;
				var newFullText = newText;
				
				if (fullText.length > startOffset) {
					// text is a portion of fullText so we have to concat it all over again
					newFullText = newText + ' ' + fullText.substr(startOffset);
				}
				
				this.$input.val(newFullText);
				this.textarea.selectionStart = lastIndexOfSymbol + 1 + newValue.length;
			}
		}
		
		return value;
	};
	
	XenForo.register('form.profilePoster textarea, textarea.StatusEditor, #ProfilePostList li .messageResponse textarea', 'XenForo.bdTagMe_ProfilePostAutoComplete');
	XenForo.register('#content.thread_view .quickReply.message textarea', 'XenForo.bdTagMe_ProfilePostAutoComplete');

}
(jQuery, this, document);