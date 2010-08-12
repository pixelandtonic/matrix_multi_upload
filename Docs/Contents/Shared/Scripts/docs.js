// Remy Sharp's HTML5 enabling script
// For discussion and comments, see: http://remysharp.com/2009/01/07/html5-enabling-script/
(function(){if(!/*@cc_on!@*/0)return;var e='abbr,article,aside,audio,canvas,datalist,details,eventsource,figure,footer,header,hgroup,mark,menu,meter,nav,output,progress,section,time,video'.split(','),i=e.length;while(i--){document.createElement(e[i])}})();


(function($){

var ee_affiliate_name = 'brandonkelly',
	external_re = new RegExp('^https?://'),
	external_ee_re = new RegExp('^(https?://(secure\\.|www\\.)?expressionengine.com\\/)([^#]*)(#(\\w+))?$'),
	ee_affiliate_re = new RegExp('^index\\.php\\?affiliate='+ee_affiliate_name),
	relative_img_re = new RegExp('/\\.\\./Images/(.*)$'),
	url_title_re = new RegExp('([\\w-]+)\.html'),
	titlePrefix = document.title,
	$nav = $('#nav'),
	$content = $('#content'),
	$selectedLink;


/**
 * Select Link
 */
var selectLink = function($link, updateHash){
	if ($selectedLink) $selectedLink.removeClass('selected');
	$selectedLink = $link.addClass('selected');

	var title = $link.html().replace(/&amp;/g, '&');
	document.title = titlePrefix+' - '+title;

	var url = $link.attr('href');

	if (updateHash) {
		var hash = url.match(url_title_re)[1];
		document.location.replace(document.location.pathname+'#'+hash);
	}

	$content.load(url+' #page', function(){
		$('img', $content).each(function(){
			var match = this.src.match(relative_img_re);

			if (match) {
				this.src = 'Contents/Local/Images/'+match[1];
			}
		});

		$('a', $content).each(function(){
			// is this an external link?
			if (this.href.match(external_re)) {
				if (! this.target) this.target = '_blank';

				// if this is a link to expressionengine.com
				// but not already an affiliate link, convert it one
				var href = this.href,
					match = href.match(external_ee_re);

				if (match && ! match[3].match(ee_affiliate_re)) {
					this.href = match[1]+'index.php?affiliate='+ee_affiliate_name
					          + (match[3] ? '&page=/'+match[3] : '')
					          + (match[5] ? '&anchor='+match[5] : '');
				}
			}
		});
	});
};


if (document.location.hash) {
	var $link = $('a[href$='+document.location.hash.substr(1)+'.html]', $nav);

	if ($link.length) {
		selectLink($link);
	}
}

if (! $selectedLink) {
	selectLink($('a:first', $nav));
}


$('a', $nav).click(function(event){
	event.preventDefault();
	selectLink($(this), true);
});


})(jQuery);
