
if(typeof tinymce!='undefined'){(function($,tinymce){XenForo.bdTagMe_EditorWrapper=function(ed){this.__construct(ed);};XenForo.bdTagMe_EditorWrapper.prototype={__construct:function(ed){this.ed=ed;this.$element=$(ed.getElement()).parent();this.symbol='@';this.regex=new RegExp(/[\s\(\)\.,!\?:;@\\\\{}]/);this.suggestionMaxLength=0;if(XenForo.bdTagMe_suggestionMaxLength){this.suggestionMaxLength=XenForo.bdTagMe_suggestionMaxLength;}
this.savedRange=false;},offset:function(){return this.$element.offset();},parents:function(){return this.$element.parents();},outerHeight:function(){return this.$element.outerHeight();},outerWidth:function(){return this.$element.outerWidth();},focus:function(){this.ed.focus();},getRange:function(){var win=this.ed.getWin();return(!window.opera&&document.all)?win.document.selection.createRange():win.getSelection().getRangeAt(0);},checkRange:function(range,offset){var latestRange=this.getRange();var win=this.ed.getWin();if(!window.opera&&document.all){if(latestRange.isEqual(range)){}else{range.select();}}else{if(latestRange.startOffset==range.startOffset&&latestRange.endOffset==range.endOffset){}else{var selection=win.getSelection();selection.removeAllRanges();selection.addRange(range);}}},saveRange:function(){this.resetRange();this.savedRange=this.getRange();},resetRange:function(){this.savedRange=false;},getFullTextFromRange:function(range){var fullText='';if(!window.opera&&document.all){var c=0;while(range.moveStart('character',-1)==-1){c++;if(range.text.charAt(0)=='@')break;}
fullText=range.text;range.moveStart('character',c);}else{var s;if(!(s=range.startContainer.nodeValue)){fullText='';}else{var c=range.startOffset-1;if(c>0){while(c>=0){fullText=s.charAt(c)+fullText;if(s.charAt(c)=='@')break;c=c-1;}}}}
return fullText;},val:function(newValue){var fullText='';var range=this.getRange();if(range){fullText=this.getFullTextFromRange(range);}
if(fullText.length==0&&this.savedRange){this.focus();range=this.savedRange;fullText=this.getFullTextFromRange(range);}
var text=fullText;var value='';var lastIndexOfSymbol=text.lastIndexOf(this.symbol);var tmp=text.substr(lastIndexOfSymbol+1);var valueFound=false;if(lastIndexOfSymbol>-1){if(this.suggestionMaxLength>0){if(text.length-lastIndexOfSymbol<this.suggestionMaxLength){valueFound=true;}}else{if(this.regex.test(tmp)==false){valueFound=true;}}}
if(valueFound){value=tmp;if(typeof newValue!='undefined'){if(!window.opera&&document.all){var x=-1*tmp.length;range.moveStart('character',x);range.moveEnd('character',x+value.length);range.pasteHTML(newValue);this.checkRange(range,x+newValue.length);}else{var container=range.startContainer;var start=range.startOffset-value.length;var offset=start+newValue.length;container.nodeValue=container.nodeValue.substr(0,start)
+newValue
+container.nodeValue.substr(range.startOffset);range.setEnd(container,offset);range.setStart(container,offset);this.checkRange(range,offset);}}}
return value;}};XenForo.bdTagMe_TinymceAutoComplete=function(ed){if(XenForo.bdTagMe_enabledTemplates){var $pageContentNode=$('#content');var pageTemplateTitle=$pageContentNode.attr('class');var isEnabledTemplate=false;for(var i in XenForo.bdTagMe_enabledTemplates){if(pageTemplateTitle==XenForo.bdTagMe_enabledTemplates[i]){isEnabledTemplate=true;}}
if(!isEnabledTemplate){return;}}
this.$input=new XenForo.bdTagMe_EditorWrapper(ed);this.ed=ed;this.url='index.php?members/find&_xfResponseType=json';var options={multiple:false,minLength:2,queryKey:'q',extraParams:{},jsonContainer:'results',autoSubmit:false};this.multiple=options.multiple;this.minLength=options.minLength;this.queryKey=options.queryKey;this.extraParams=options.extraParams;this.jsonContainer=options.jsonContainer;this.autoSubmit=options.autoSubmit;this.selectedResult=0;this.loadVal='';this.$results=false;this.resultsVisible=false;ed.onKeyDown.add($.context(this,'edKeyDown'));};XenForo.bdTagMe_TinymceAutoComplete.prototype=$.extend(true,{},XenForo.AutoComplete.prototype);XenForo.bdTagMe_TinymceAutoComplete.prototype.edKeyDown=function(ed,e){var code=e.keyCode||e.charCode,prevent=true;switch(code)
{case 40:case 38:case 27:if(!this.resultsVisible){return;}}
this.keystroke(e);};XenForo.bdTagMe_TinymceAutoComplete.prototype.getPartialValue=function(){return this.$input.val();};XenForo.bdTagMe_TinymceAutoComplete.prototype.addValue=function(value){return this.$input.val(value);};var showResultsOrig=XenForo.bdTagMe_TinymceAutoComplete.prototype.showResults;XenForo.bdTagMe_TinymceAutoComplete.prototype.showResults=function(results){this.$input.saveRange();showResultsOrig.call(this,results);};var hideResultsOrig=XenForo.bdTagMe_TinymceAutoComplete.prototype.hideResults;XenForo.bdTagMe_TinymceAutoComplete.prototype.hideResults=function(){hideResultsOrig.call(this);this.$input.resetRange();};tinymce.create('tinymce.plugins.XenForobdTagMe',{init:function(ed,url){new XenForo.bdTagMe_TinymceAutoComplete(ed);},getInfo:function(){return{longname:'[bd] Tag Me',author:'xfrocks',authorurl:'http://xfrocks.com',version:"1.2"};}});tinymce.PluginManager.add('xenforo_bdtagme',tinymce.plugins.XenForobdTagMe);}(jQuery,tinymce));}