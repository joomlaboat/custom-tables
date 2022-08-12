function ESShowHide(objname) {
    const obj = document.getElementById(objname);

    if (obj.style.display == "block")
        obj.style.display = "none";
    else
        obj.style.display = "block";
}

function CustomTablesChildClick(BoxName, DivName) {
    const obj = document.getElementById(BoxName);
    const divobj = document.getElementById(DivName);

    if (obj.checked)
        divobj.style.display = "block";
    else
        divobj.style.display = "none";

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

    const charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57))
        return true;

    if (charCode == 8 || charCode == 13)
        return true;

    let v = el.value;

    if (el.selectionEnd - el.selectionStart > 0)
        return true;

    if (el.selectionStart < v.length)
        return true;

    if (el.maxLength == v.length)
        return true;

    if (charCode == 46) {
        v = v.replace(".", "");
        p = parseFloat(v);
        if (isNaN(p))
            p = 0;

        el.value = p;
        return true;
    }

    //check selection
    let d = (v.split('.')[1] || []).length;
    if (d > decimals)
        d = decimals;

    p = parseFloat(v);
    if (isNaN(p))
        p = 0;

    if (d == 0) {
        const g = "" + p;

        if (v == '0') {
            el.value = p + '.';
            return true;
        }

        if (g.length != 0) {
            if (g.length == 1 && p != 0)
                el.value = p + '.';

            return true;
        }

        let vv = '0.';

        for (d = 1; d < decimals; d++)
            vv += '0';

        el.value = vv;

        return true;
    }

    if (d == decimals) {
        p = p * 10;
        el.value = p.toFixed(decimals - 1);
        return true;
    }
    return true;
}
