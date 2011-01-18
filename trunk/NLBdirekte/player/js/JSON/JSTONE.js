/*
Script: JSTONE.js

JavaScript Temporary Object Notation Engine:	
	JSTONE is a simple way to save temporary client informations.
	These informations should be every JSON compatible variable (Number, Boolean, null, Array, Object, String, Date) but with some simple hack these shuld be constructors, functions and other type of variables too.
	
	With JSTONE client doesn't require cookies with value "0" as expiration date because every information will be available inside used window even if user refreshes the page.
	JSTONE doesn't use Ajax to save informations so it's compatible with every host.
	JSTONE simply allow developers to save temporary data that will not be lost on window update (refresh / F5) or using always the same window session.

	With JSTONE You can (using same browser window):
		-	save informations while user browses your site
		-	transport informations on every sub domain or url
		-	transport informations between different sites/domains
		-	remember data wrote inside forms, input, textarea or other editable tags
		-	save extra informations (functions or constructors too) solving cache problems after first download (should work with disabled cache too)
		-	monitor each step user does inside one or more pages, saving history, data, layout (using for example innerHTML) and much more
	
	With JSTONE You can't:
		-	transport informations between two different browser windows (CTRL + N, CTRL + T and generic browser different windows) without a server side language help
		-	modify window.name parameter with your own scripts (or external libraries)
		-	believe on data security without server-side security checks (policy and security features are inside a client script, there's anything secure with it)
	
	To download last version of this script use this link: <http://www.devpro.it/javascript_id_159.html>

Version:
	0.3 - wow, it really works! (and doesn't have conflicts with multiple objects)

Compatibility:
	FireFox - Version 1, 1.5, 2 and 3
	Internet Explorer - Version 5, 5.5, 6 and 7
	Opera - 8 and 9 (probably 7 too)
	Safari - Version 2 (probably 1 too)
	Konqueror - Version 3 or greater

Dependencies:
	JSTONE instances requires a generic JSON parser to encode/decode informations.
	You could use your favourite JSON parser sending encode and decode methods as first constructor argument.
	>// Example:
	>MyJSTONE = new JSTONE({
	>	encode:function(obj){return obj.toJSONString()},
	>	decode:function(str){return str.parseJSON()}
	>});

	If you've not a JSON parser, You could use <JSON.js> one just sending them on JSTONE constructor.
	>MyJSTONE = new JSTONE(JSON);
	
Author:
	Andrea Giammarchi, <http://www.3site.eu>

License:
	>Copyright (C) 2007 Andrea Giammarchi - www.3site.eu
	>	
	>Permission is hereby granted, free of charge,
	>to any person obtaining a copy of this software and associated
	>documentation files (the "Software"),
	>to deal in the Software without restriction,
	>including without limitation the rights to use, copy, modify, merge,
	>publish, distribute, sublicense, and/or sell copies of the Software,
	>and to permit persons to whom the Software is furnished to do so,
	>subject to the following conditions:
	>
	>The above copyright notice and this permission notice shall be included
	>in all copies or substantial portions of the Software.
	>
	>THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
	>INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	>FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
	>IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
	>DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
	>ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
	>OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

/*
Constructor: JSTONE
	Create a JSTONE instance. You can create only one instance for each page.

Arguments:
	Object - generic object with at least 2 methods, encode and decode. Encode should accept a generic JavaScript variable and returns JSON version of its value while decode should accept a valid JSON string and return its native value.
	[Boolean] - optional "free" parameter to allow multiple site informations transport using the same browser window. By default this value is false and this means that informations will be available only in the same first, second or third level domains.
	[Boolean] - optional "clear" parameter to remove everything from window.name on page unload.
	
Note:
	JavaScript is a scripting language parsed runtime and its native functions behaviour should be changed by malicious user. It's a good practice to don't save private data/informations inside cookies and in this case should be a good practice don't save these kind of informations inside JSTONE too.
	Please remember that JSTONE is based on window.name property and everyone, from every site, should read this property parsing them using JSON. This is another reason to don't believe on JSTONE object for personal data or private informations even if You use last clear argument to remove every information from window.name property.
*/
function JSTONE(JSON, free, clear){

	/* Section: Methods - Public */
	
	/*
	Method: clear
		destroy every private object information.
	
	Example:
		>MySTONE = new JSTONE(JSON);
		>
		>// verify if JSTONE was used in your site
		>if(MySTONE.read("domain") !== "mysite")
		>	MySTONE.clear();
		>
		>MySTONE.write("domain", "mysite");
	
	Example [paranoia version]:
		>// using server side informations, in this case a PHP session id
		>// malicious users cannot know which SID outgoing client will recieve
		>SID = "<?php echo session_id(); ?>";
		>
		>MySTONE = new JSTONE(JSON);
		>if(MySTONE.read("SID") !== SID) MySTONE.write("domain", MySTONE.clear() || SID);
		>// next time above check will not clear informations so You can believe on your instance informations.
	
	Note:
		Malicious users should set window.name in their pages manually adding other site parameters.
		This check should be based on server-side informations such session ID.
		If generic STONE.read("SID") is different, session was expired or user doesn't come from your site.
		In this way You can have better and more secure (just a bit)  control with your JSTONE instance.
	*/
	this.clear = function(){
		for(var	key = k.split("."), i = 0, l = key.length - 1, t = get(); i < l; i++) {
			if(!t[key[i]])t[key[i]] = {};
			t = t[key[i]];
		};	t[key[i]] = {};
		sync();
	};
	
	
	/*
	Method: read
		try to find a variable and return its value or undefined.
	
	Arguments:
		String - saved namespace (for example: "user" or "user.name" or "user.data.name" ... )
	
	Returns:
		Object - Generic JavaScript variable or undefined
	
	Example 1:
		>MySTONE = new JSTONE(JSON);
		>
		>if(MySTONE.read("user"))
		>	with(MySTONE.read("user")) {
		>		alert([
		>			name + " " + surname,
		>			address + " " + city,
		>			country
		>		].join("\n"));
		>	}
		>else
		>	document.getElementById("myForm").onsubmit = function() {
		>		var	input = this.getElementsByTagName("input");
		>		MySTONE.write("user", {
		>			name:input[0],
		>			surname:input[1],
		>			address:input[2],
		>			city:input[3],
		>			country:input[4]
		>		});
		>	};
	
	Example 2:
		>MySTONE = new JSTONE(JSON);
		>MySTONE.write("informations", {browser:navigator.userAgent, page:location.href});
		>MySTONE.read("informations.browser");	// user agent
		>MySTONE.read("informations").page;	// this page
	
	Note:
		JSTONE search function creates automatically empty objects before last searched value if these are not defined.
		If You search "user.name", for example, "user" namespace will be created automatically.
	*/
	this.read = function(key){
		var	o = find(key);
		return	o.o[o.key]
	};

	
	/*
	Method: write
		set a generic variable into selected namespace
	
	Arguments:
		String - namespace to set (for example: "user" or "user.name" or "user.data.name" ... )
		Object - Generic JavaScript variable to set
	
	Example:
		>MySTONE = new JSTONE(JSON);
		>MySTONE.write("informations", {browser:navigator.userAgent, page:location.href});
		>MySTONE.write("informations.user", {id:1, data:{name:"Mickey"}});
		>MySTONE.write("informations.user.data.age", 29);
	
	Note:
		JSTONE search function creates automatically empty objects before last namespace value if these are not defined.
		Namespace uses dot convention "." to separate each value.
	*/
	this.write = function(key, value){
		var	o = find(key);
			o.o[o.key] = value;
		sync();
	};


	/* Section: Properties - Private */
	
	/*
	Property: Private
	
	List:
		Function - 'find' - internal function to find or set values using choosed namespace
		Function - 'get' - refresh internal object decoding window.name
		Function - 'sync' - update window.name encoding internal object
		Function - 'unload' - unload event if optional third argument is true
		String - 'k' - visited domain name or universal namespace to send or recieve data between different sites.
		Object - 'o' - object where data are stored using parsed domain as private namespace prefix.
	*/
	var	find = function(key){
			for(var i = 0, l = (key = k.concat(".", key).split(".")).length - 1, t = get(); i < l; i++) {
				if(!t[key[i]])t[key[i]] = {};
				t = t[key[i]];
			};	return {o:t, key:key.pop()}
		},
		get = function(){return	o = window.name ? JSON.decode(window.name) : o},
		sync = function(){window.name = JSON.encode(o)},
		unload = clear ? function(){window.name = ""} : null,
		k = !free ? location.href.split("/").slice(2,3)[0].replace(/:[0-9]+/, "") : "o.o",
		o = {};
	get();
	if(unload)
		window.addEventListener ? window.addEventListener("unload", unload, false) : window.attachEvent("on".concat("unload"), unload);
};