//TAB切换
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

function donghua(obj){
	  var speed=20;
	  var demoh  =  document.getElementById(obj);
	  var demoh1 =  document.getElementById(obj+'_1');
	  var demoh2 =  document.getElementById(obj+'_2');
	  demoh2.innerHTML=demoh1.innerHTML;
	  function wfdh(){
		  if(demoh2.offsetWidth-demoh.scrollLeft<=0){demoh.scrollLeft-=demoh1.offsetWidth;}
		  if(demoh2.offsetWidth-demoh.scrollLeft>0){demoh.scrollLeft++;}
	  }
	  var MyMarh=setInterval(wfdh,speed);
	  demoh.onmouseover=function() {clearInterval(MyMarh);}
	  demoh.onmouseout=function() {MyMarh=setInterval(wfdh,speed);}
}
function showon(obj,onobj,id){
	$(obj+' '+ onobj).click(function(){
		if($(obj).hasClass('on')){
			$(obj).removeClass('on');
		}else{
		  $(obj).addClass('on');
		}
	});
	if(id){
		$('#catlist_'+id).addClass('on');
		$('#catlist_'+id).parents('.folder').addClass('on');
	}
}

function Floaters() {
		this.delta=0.15;
		this.playid =null;
		this.items	= [];
		this.addItem	= function(id,x,y,content) {
			var newItem = {};
			newItem.object = document.getElementById(id);

			if(x==0){
				objw= newItem.object.offsetWidth;
				var body = (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body;
				newItem.x = x = body.scrollLeft + (body.clientWidth - objw)/2;
				newItem.y = y;
			}else{
				newItem.x = x;
				newItem.y = y;
			}

			this.items[this.items.length]		= newItem;
		}
		this.play =function(varname){
			this.playid = setInterval(varname+'.plays()',30);
		}
		this.close = function(obj){
			document.getElementById(obj).style.display='none';
			 //clearInterval(this.playid);
		}
}
Floaters.prototype.plays = function(){
	var diffY;
	if (document.documentElement && document.documentElement.scrollTop)
	{
		diffY = document.documentElement.scrollTop;
	}
	else if (document.body)
	{
		diffY = document.body.scrollTop;
	}else{}

	for(var i=0;i<this.items.length;i++) {
		var obj = this.items[i].object;
		var followObj_y = this.items[i].y;
		var total = diffY + followObj_y;
		if(this.items[i].x >= 0){
			obj.style['left'] = this.items[i].x+ 'px';
		}else{
			obj.style['right'] = Math.abs(this.items[i].x)+ 'px';
		}
		if( obj.offsetTop != total)
		{
			var oldy = (total - obj.offsetTop) * this.delta;
				newtop = obj.offsetTop + ( oldy>0?1:-1 ) * Math.ceil( Math.abs(oldy) );
			obj.style['top'] = newtop + 'px';
		}
	}
}


function changeorder(obj,productid,del,ordercall){
	var objs  =  document.getElementById(obj);
	var data={'productid': productid,'num':objs.value};
	$.ajax({
		type:"POST",
		url: ROOT+"/index.php?m=Order&a=ajax&del="+del,
		data: data,
		timeout:"4000",
		dataType:"JSON",
		success: function(data){
			ordercall.call(this,obj,productid,del,data);
		},
		error:function(){
			alert("time out,try it");
		}
	});
}