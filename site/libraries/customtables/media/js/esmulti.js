function ESShowHide(objName) {
	const obj = document.getElementById(objName);

	if (obj.style.display === "block")
		obj.style.display = "none";
	else
		obj.style.display = "block";
}

function CustomTablesChildClick(BoxName, DivName) {
	const obj = document.getElementById(BoxName);
	const divObject = document.getElementById(DivName);

	if (obj.checked)
		divObject.style.display = "block";
	else
		divObject.style.display = "none";

	return 0;
}

function ESCheckAll(prefix, aList) {
	for (let i = 0; i < aList.length; i++) {
		const obj = document.getElementById(prefix + "_" + aList[i]);
		obj.checked = true;
	}
}

function ESUncheckAll(prefix, aList) {
	for (let i = 0; i < aList.length; i++) {
		const obj = document.getElementById(prefix + "_" + aList[i]);
		obj.checked = false;
	}
}

function ESsmart_float(el, evt, decimals) {
	let p;
	if (decimals < 1)
		return true;

	const charCode = parseInt((evt.which) ? evt.which : evt.keyCode);
	if (charCode !== 46 && charCode > 31 && (charCode < 48 || charCode > 57))
		return true;

	if (charCode === 8 || charCode === 13)
		return true;

	let vString = el.value;

	if (el.selectionEnd - el.selectionStart > 0)
		return true;

	if (el.selectionStart < vString.length)
		return true;

	if (el.maxLength === vString.length)
		return true;

	if (charCode === 46) {
		vString = vString.replace(".", "");
		p = parseFloat(vString);
		if (isNaN(p))
			p = 0;

		el.value = p;
		return true;
	}

	//check selection
	let d = (vString.split('.')[1] || []).length;
	if (d > decimals)
		d = decimals;

	p = parseFloat(vString);
	if (isNaN(p))
		p = 0;

	if (d === 0) {
		const g = "" + p;

		if (vString === '0') {
			el.value = p + '.';
			return true;
		}

		if (g.length !== 0) {
			if (g.length === 1 && p !== 0)
				el.value = p + '.';

			return true;
		}

		let vv = '0.';

		for (d = 1; d < decimals; d++)
			vv += '0';

		el.value = vv;

		return true;
	}

	if (d === decimals) {
		p = p * 10;
		el.value = p.toFixed(decimals - 1);
		return true;
	}
	return true;
}
