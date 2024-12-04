function CreateHTTPRequestObject() {
	// although IE supports the XMLHttpRequest object, but it does not work on local files.
	var forceActiveX = (window.ActiveXObject && location.protocol === "file:");
	if (window.XMLHttpRequest && !forceActiveX) {
		return new XMLHttpRequest();
	} else {
		try {
			return new ActiveXObject("Microsoft.XMLHTTP");
		} catch (e) {
		}
	}
	alert("Your browser doesn't support XML handling!");
	return null;
}

function CreateMSXMLDocumentObject() {
	if (typeof (ActiveXObject) != "undefined") {
		var progIDs = [
			"Msxml2.DOMDocument.6.0",
			"Msxml2.DOMDocument.5.0",
			"Msxml2.DOMDocument.4.0",
			"Msxml2.DOMDocument.3.0",
			"MSXML2.DOMDocument",
			"MSXML.DOMDocument"
		];
		for (var i = 0; i < progIDs.length; i++) {
			try {
				return new ActiveXObject(progIDs[i]);
			} catch (e) {
			}
			;
		}
	}
	return null;
}

function CreateXMLDocumentObject(rootName) {
	if (!rootName) {
		rootName = "";
	}
	var xmlDoc = CreateMSXMLDocumentObject();
	if (xmlDoc) {
		if (rootName) {
			var rootNode = xmlDoc.createElement(rootName);
			xmlDoc.appendChild(rootNode);
		}
	} else {
		if (document.implementation.createDocument) {
			xmlDoc = document.implementation.createDocument("", rootName, null);
		}
	}

	return xmlDoc;
}

function ParseHTTPResponse(httpRequest) {
	var xmlDoc = httpRequest.responseXML;

	// if responseXML is not valid, try to create the XML document from the responseText property
	if (!xmlDoc || !xmlDoc.documentElement) {
		if (window.DOMParser) {
			var parser = new DOMParser();
			try {
				xmlDoc = parser.parseFromString(httpRequest.responseText, "text/xml");
			} catch (e) {
				alert("XML parsing error");
				return null;
			}
			;
		} else {
			xmlDoc = CreateMSXMLDocumentObject();
			if (!xmlDoc) {
				return null;
			}
			xmlDoc.loadXML(httpRequest.responseText);

		}
	}

	// if there was an error while parsing the XML document
	var errorMsg = null;
	if (xmlDoc.parseError && xmlDoc.parseError.errorCode != 0) {
		errorMsg = "XML Parsing Error: " + xmlDoc.parseError.reason
			+ " at line " + xmlDoc.parseError.line
			+ " at position " + xmlDoc.parseError.linepos;
	} else {
		if (xmlDoc.documentElement) {
			if (xmlDoc.documentElement.nodeName == "parsererror") {
				errorMsg = xmlDoc.documentElement.childNodes[0].nodeValue;
			}
		}
	}
	if (errorMsg) {
		alert(errorMsg);
		return null;
	}

	// ok, the XML document is valid
	return xmlDoc;
}

// returns whether the HTTP request was successful
function IsRequestSuccessful(httpRequest) {
	// IE: sometimes 1223 instead of 204
	var success = (httpRequest.status == 0 ||
		(httpRequest.status >= 200 && httpRequest.status < 300) ||
		httpRequest.status == 304 || httpRequest.status == 1223);

	return success;
}


function convertHtmlToText(returnText) {

	//-- remove BR tags and replace them with line break
	returnText = returnText.replace(/<br>/gi, "\n");
	returnText = returnText.replace(/<br\s\/>/gi, "\n");
	returnText = returnText.replace(/<br\/>/gi, "\n");

	//-- remove P and A tags but preserve what's inside them
	returnText = returnText.replace(/<p.*>/gi, "\n");
	returnText = returnText.replace(/<a.*href="(.*?)".*>(.*?)<\/a>/gi, " $2 ($1)");

	//-- remove all inside SCRIPT and STYLE tags
	returnText = returnText.replace(/<script.*>[\w\W]{1,}(.*?)[\w\W]{1,}<\/script>/gi, "");
	returnText = returnText.replace(/<style.*>[\w\W]{1,}(.*?)[\w\W]{1,}<\/style>/gi, "");
	//-- remove all else
	returnText = returnText.replace(/<(?:.|\s)*?>/g, "");

	//-- get rid of more than 2 multiple line breaks:
	returnText = returnText.replace(/(?:(?:\r\n|\r|\n)\s*){2,}/gim, "\n\n");

	//-- get rid of more than 2 spaces:
	returnText = returnText.replace(/ +(?= )/g, '');

	//-- get rid of html-encoded characters:
	returnText = returnText.replace(/&nbsp;/gi, " ");
	returnText = returnText.replace(/&amp;/gi, "&");
	returnText = returnText.replace(/&quot;/gi, '"');
	returnText = returnText.replace(/&lt;/gi, '<');
	returnText = returnText.replace(/&gt;/gi, '>');

	//-- return
	return returnText;
}
