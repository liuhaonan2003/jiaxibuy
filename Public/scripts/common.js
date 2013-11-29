function setHomePage(url){
	if(!window.confirm("是否设置http://"+document.location.host+"为首页?")) return false;
    if (window.sidebar)
    {
        try { 
           netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect"); 
        } 
        catch (e) 
        {  
           alert("此操作被浏览器拒绝！\n请在浏览器地址栏输入“about:config”并回车\n然后将[signed.applets.codebase_principal_support]设置为“true”"); 
        } 
        var prefs = Components.classes['@mozilla.org/preferences-service;1'].getService(Components.interfaces.nsIPrefBranch);
            prefs.setCharPref('browser.startup.homepage',url);

    }
}

function addBookmark(title,url) {
    if (window.sidebar) {
        window.sidebar.addPanel(title, url,"");
    } else if( document.all ) {
        window.external.AddFavorite( url, title);
    } else if( window.opera && window.print ) {
        return true;
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
