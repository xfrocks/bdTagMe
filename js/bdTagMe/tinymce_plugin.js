(function($, tinymce) {
	XenForo.bdTagMe_EditorWrapper = function(ed) { this.__construct(ed); };
	XenForo.bdTagMe_EditorWrapper.prototype = {
		__construct: function(ed) {
			this.ed = ed;
			this.$element = $(ed.getElement()).parent();
			
			this.symbol = '@';
			// PLEASE UPDATE THE REGULAR EXPRESSION IN PHP IF YOU CHANGE IT HERE
			// bdTagMe_DataWriter_DiscussionMessagePost::set()
			this.regex = new RegExp(/[\s\(\)\[\]\.,!\?:;@]/);
		},
		
		offset: function() {
			return this.$element.offset();
		},
		
		parents: function() {
			return this.$element.parents();
		},
		
		val: function(newValue) {
			var content = this.ed.getContent();
			var selection = this.ed.selection;
			var range = selection.getRng();
			if (range.commonAncestorContainer) {
				var fullText = range.commonAncestorContainer.textContent;
			} else {
				// something is wrong
				// we simply don't do anything
				var fullText = '';
			}
			var text = fullText;
			var value = '';
			
			if (fullText.length > range.startOffset) {
				// ignore the text after the cursor
				text = fullText.substr(0, range.startOffset);
			}
			
			// get the text after the last symbol
			var lastIndexOfSymbol = text.lastIndexOf(this.symbol);
			var tmp = text.substr(lastIndexOfSymbol + 1);
			if (lastIndexOfSymbol > -1 && this.regex.test(tmp) == false) {
				// something has been found!
				value = tmp;
				
				if (typeof newValue != 'undefined') {
					var newText = text.substr(0, lastIndexOfSymbol + 1) + newValue;
					var newFullText = newText;
					
					if (fullText.length > range.startOffset) {
						// text is a portion of fullText so we have to concat it all over again
						newFullText = newText + ' ' + fullText.substr(range.startOffset);
					}
					
					range.commonAncestorContainer.textContent = newFullText;
					this.ed.selection.select(range.startContainer, true);
					this.ed.selection.collapse(false);
					this.ed.focus();
				}
			}
			
			return value;
		},
		
		outerHeight: function() {
			return this.$element.outerHeight();
		},
		
		outerWidth: function() {
			return this.$element.outerWidth();
		}
	};
	
	XenForo.bdTagMe_AutoComplete = function(ed) {
		// copied from XenForo.AutoComplete.__construct
		
		this.$input = new XenForo.bdTagMe_EditorWrapper(ed);
		this.ed = ed;
		this.url = 'index.php?members/find&_xfResponseType=json';

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

		this.selectedResult = 0;
		this.loadVal = '';
		this.$results = false;
		this.resultsVisible = false;

		ed.onKeyDown.add($.context(this, 'edKeyDown'));
	};
	XenForo.bdTagMe_AutoComplete.prototype = XenForo.AutoComplete.prototype;
	XenForo.bdTagMe_AutoComplete.prototype.edKeyDown = function(ed, e) {
		var code = e.keyCode || e.charCode, prevent = true;

		switch(code)
		{
			case 40: // down
			case 38: // up
			case 27: // esc
				if (!this.resultsVisible) {
					// if our results is not visible
					// stop calling the keystroke method
					// or user won't be able to navigate around
					// XenForo should fix this...
					return;
				}
		}
		
		this.keystroke(e);
	};
	XenForo.bdTagMe_AutoComplete.prototype.getPartialValue = function() {
		return this.$input.val();
	};
	XenForo.bdTagMe_AutoComplete.prototype.addValue = function(value) {
		return this.$input.val(value);
	};
	
	tinymce.create('tinymce.plugins.XenForobdTagMe', {
		init: function(ed, url) {
			new XenForo.bdTagMe_AutoComplete(ed);
		},
		
		getInfo: function() {
			return {
				longname : '[bd] Tag Me',
				author : 'xfrocks',
				authorurl : 'http://xfrocks.com',
				version : "1.2"
			};
		}
	});
		
	// Register plugin
	tinymce.PluginManager.add('xenforo_bdtagme', tinymce.plugins.XenForobdTagMe);
}(jQuery, tinymce));