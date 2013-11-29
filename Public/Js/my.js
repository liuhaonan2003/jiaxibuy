function selectall(name) {
	if ($("#check_box").attr("checked")) {
		$("input[name='"+name+"']").each(function() {
			this.checked=true;
		});
	} else {
		$("input[name='"+name+"']").each(function() {
			this.checked=false;
		});
	}
}

//TAB�л�
function Tabs(id,title,content,box,on,action){
	if(action){
		  $(id+' '+title).click(function(){
			  $(this).addClass(on).siblings().removeClass(on);
			  $(content+" > "+box).eq($(id+' '+title).index(this)).show().siblings().hide();
		  });
	  }else{
		  $(id+' '+title).mouseover(function(){
			  $(this).addClass(on).siblings().removeClass(on);
			  $(content+" > "+box).eq($(id+' '+title).index(this)).show().siblings().hide();
		  });
	  }
}

function openwin(url,title,width,height){
	art.dialog.open(url, {
			id: 'license',
			title: title,
			lock: 'true',
			window: 'top',
			width: width,
			height: height,
			yesFn: function(iframeWin, topWin){
					iframeWin.document.getElementById('myform').submit();
					return false;

			},
			noFn: function(){}
		}
	);
}

function opendialog(url,title,width,height){
	art.dialog.open(url, {
			id: 'license',
			title: title,
			lock: 'true',
			window: 'top',
			width: width,
			height: height,
			yesFn: function(iframeWin, topWin){					
					var form = iframeWin.document.getElementById('dosubmit');form.click();
					return false;

			},
			noFn: function(){}
		}
	);
}