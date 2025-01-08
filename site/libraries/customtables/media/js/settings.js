/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2025. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
function updateScreenshots() {
	const obj = document.getElementById("imageLoadProgress");
	const url = "/administrator/index.php?option=com_customtables&view=settings";
	let params = "";
	params += "&task=settings.RefreshTemplates";
	params += "&clean=1";

	let http = CreateHTTPRequestObject();   // defined in ajax.js

	if (http) {
		http.open("POST", url, true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.onreadystatechange = function () {

			if (http.readyState === 4) {
				const res = http.response;

				if (!isJSONValid(res)) {
					alert(res);
					return false;
				}

				const list = JSON && JSON.parse(res) || $.parseJSON(res);

				let p = 0;
				if (list.completed !== 0)
					p = Math.floor(100 / (list.total / list.completed));

				document.getElementById("imageLoadProgress_completed").innerHTML = list.completed;
				document.getElementById("imageLoadProgress_total").innerHTML = list.total;

				obj.innerHTML = p + "%";
				obj.style.width = p + "%";

				if (list.total > list.completed) {
					setTimeout(function () {
						updateScreenshots();
					}, 100);
				}
			}
		};
		http.send(params);
	} else {
		obj.innerHTML = "<span style='color:red;'>Cannot Save</span>";
	}
}

function isJSONValid(text) {
	return /^[\],:{}\s]*$/.test(text.replace(/\\["\\\/bfnrtu]/g, '@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').replace(/(?:^|:|,)(?:\s*\[)+/g, ''));
}
