M.block_phrasecomment = {

	init: function(Y,options){
        
		function getSelectionObject(){
			var userSelection;
			if (window.getSelection) {
				userSelection = window.getSelection();
			}
			else if (document.selection) { // should come last; Opera!
				userSelection = document.selection.createRange();
				event.cancelBubble = true
			}
			return userSelection
		}

		function getSelectionText(){
			var userSelection = getSelectionObject();
			var selectedText = userSelection;
			if (userSelection.text)
				selectedText = userSelection.text;
			return selectedText;
		}

		function getRangeObject(selectionObject) {
			if (selectionObject.getRangeAt)
				return selectionObject.getRangeAt(0);
			else { // Safari!
				var range = document.createRange();
				range.setStart(selectionObject.anchorNode,selectionObject.anchorOffset);
				range.setEnd(selectionObject.focusNode,selectionObject.focusOffset);
				return range;
			}
		}

		function createRangeObject(qno,start,end){
			var answer = YAHOO.util.Selector.query('.answer', '#q'+qno, true);
			var range = document.createRange();
			range.setStart(answer,start);
			range.setEnd(answer,end);
		}


		var contextmenucontent = "<style type='text/css'> .cmenu {margin: 0; padding: 0.3em; list-style-type: none; background-color: white;} .cmenu li:hover {} .cmenu hr {border: 0; border-bottom: 1px solid grey; margin: 3px 0px 3px 0px; width: 10em;} .cmenu a {border: 0 !important;} .cmenu a:hover {text-decoration: underline !important;} .cmenu .topSep {font-size: 90%; border-top: 1px solid gray; margin-top: 0.3em; padding-top: 0.3em;} th, td {text-align: left; padding-right: 1em;} table {margin: 0 0 0.4em 1.3em; border: 1px solid rgb(240, 240, 240);}</style><div id='divContext' style='border: 1px solid blue; display: none; position: absolute'> <ul class='cmenu'> <li><a id='aAddCommentBlock' href='#'>Comment</a></li></ul> </div> ";
		var contextmenu = document.createElement('div');
		contextmenu.innerHTML=contextmenucontent;
		document.getElementById('page-content').appendChild(contextmenu);
		while (contextmenu.firstChild) {
		    document.getElementById('page-content').appendChild(contextmenu.firstChild);
		}
		   	
		var _mouseOverContext 	= false;       
		var _divContext 		= document.getElementById('divContext');  
	
		InitContext();
		function InitContext(){
			_divContext.onmouseover = function() { _mouseOverContext = true; };
			_divContext.onmouseout = function() { _mouseOverContext = false; };
			document.body.onmousedown = ContextMouseDown;
			var answerdivs = YAHOO.util.Dom.getElementsByClassName('answer', 'div');
			for(i in answerdivs){
				answerdivs[i].onmouseup = ContextShow;
			}
		}

		function ContextMouseDown(event){
			if(_mouseOverContext) return;
			if (event == null) event = window.event;
			if (event.button == 2){
				_replaceContext = true;
			}else if (!_mouseOverContext)
				_divContext.style.display = 'none';
		}

		function ContextShow(event){
			if(_mouseOverContext) return;
			if (event == null) event = window.event;
			if(getSelectionText()!=""){
				var selection = getSelectionObject();
				if(selection.anchorNode.parentNode===selection.focusNode.parentNode){ // ToDo: Check
					var parent = selection.anchorNode.parentNode;
					while (! YAHOO.util.Dom.hasClass(parent,"que")){ parent=parent.parentNode;}
					var qid = parent.id.substring(1);
					var startOffset = selection.anchorOffset;
					var endOffset 	= selection.focusOffset;
					document.getElementById("aAddCommentBlock").setAttribute("onclick","AddCommentBlock("+qid+","+startOffset+","+endOffset+");");
					document.getElementById("aAddCommentBlock").onclick=function(event){AddCommentBlock(qid,startOffset,endOffset);if (event.preventDefault) { event.preventDefault(); } else { event.returnValue = false; } };
				}
				var scrollTop = document.body.scrollTop ? document.body.scrollTop : document.documentElement.scrollTop;
		    	var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft : document.documentElement.scrollLeft;
				_divContext.style.display = 'none';
				_divContext.style.left = event.clientX + scrollLeft + 'px';
				_divContext.style.top = event.clientY + scrollTop - 100 + 'px';
				_divContext.style.display = 'block';

				_replaceContext = false;
			}
            return false;
		}
        
        function CloseContext() {
            _mouseOverContext = false;
            _divContext.style.display = 'none';
        }

		function AddCommentBlock(qid,start,end){
			//alert(qid+' '+start+' '+end);
            var loadUrl = M.cfg.wwwroot+'/blocks/phrasecomments/addphraseblock_ajax.php?sesskey='+M.cfg.sesskey+'&qid='+qid+'&start='+start+'&end='+end+'&action=addblock&contextid='+options.contextid;     
            var callback = {
                success: function(o){
                    if(o.responseText!='')
                        alert(o.responseText);
                    CloseContext();
                    location.reload(true);
                },
                failure: function(o){
                    //alert(o.responseText);
                },
                argument: [],
                cache: false
            };

            var transaction = YAHOO.util.Connect.asyncRequest('GET', loadUrl, callback, null);
			return false;
		}
	}

}
