/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright Copyright (C) 2018-2024. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
const codemirror_editors = [];
let codemirror_active_index = 0;
let codemirror_active_areatext_id = null;
let codemirror_theme = 'eclipse';
let temp_params_tag = '';
let layout_tags = [];
let layout_tags_loaded = false;
let tagsets = [];
let current_layout_type = 0;

function updateTagsParameters() {

    if (type_obj == null)
        return;

    current_layout_type = parseInt(type_obj.value);
    if (isNaN(current_layout_type))
        current_layout_type = 0;

    let t1 = findTagSets(current_layout_type, "1");
    let t2 = findTagSets(current_layout_type, "2");
    let t3 = findTagSets(current_layout_type, "3");
    let t4 = findTagSets(current_layout_type, "4");

    tagsets = t1.concat(t2, t3, t4);

    if (tagsets.length > 0)
        do_render_current_TagSets();
    else {
        const box = document.getElementById("layouteditor_modal_content_box");
        box.innerHTML = '<p class="msg_error">Unknown Field Type</p>';
    }

    updateFieldsBox();
}

function findTagSets(layouttypeid, priority) {
    const tagsets_ = [];
    for (let i = 0; i < layout_tags.length; i++) {
        const a = layout_tags[i]["@attributes"];

        let p = 0;
        if (typeof (a.priority) != "undefined")
            p = layout_tags[i]["@attributes"].priority;

        if (p === priority) {
            let layouttypes = "";
            if (typeof (a.layouttypes) != "undefined")
                layouttypes = a.layouttypes;

            const lta = layouttypes.split(',');

            if (layouttypes === "" || lta.indexOf(layouttypeid + "") !== -1)
                tagsets_.push(layout_tags[i]);
        }
    }
    return tagsets_;
}

function loadTagParams(type_id, tags_box) {

    type_obj = document.getElementById(type_id);

    if (!layout_tags_loaded) {
        loadTags(type_id, tags_box);
    } else {
        updateTagsParameters();
    }
}

function loadTags(type_id, tags_box) {
    type_obj = document.getElementById(type_id);

    let url = '';
    if (window.Joomla instanceof Object) {
        let parts = location.href.split("/administrator/");
        url = parts[0] + '/index.php?option=com_customtables&view=xml&xmlfile=tags&Itemid=-1';
    } else if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar')) {
        let parts = location.href.split("wp-admin/admin.php?");
        url = parts[0] + 'wp-admin/admin.php?page=customtables-api-xml&xmlfile=tags';
    } else {
        alert('loadTags: CMS Not Supported.');
        return;
    }

    const params = "";

    let http = CreateHTTPRequestObject();   // defined in ajax.js

    if (http) {
        http.open("GET", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.onreadystatechange = function () {

            if (http.readyState === 4) {
                const res = http.response;

                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(res, "text/xml");

                if (xmlDoc.getElementsByTagName('parsererror').length) {
                    //tags_box_obj.innerHTML='<p class="msg_error">Error: '+(new XMLSerializer()).serializeToString(xmlDoc)+'</p>';
                    return;
                }

                const s = xmlToJson(xmlDoc);

                layout_tags = s.layouts.tagset;

                layout_tags_loaded = true;
                loadTagParams(type_id, tags_box);

            }
        };
        http.send(params);
    } else {
        //error
        //tags_box_obj.innerHTML='<p class="msg_error">Cannot connect to the server</p>';
    }
}

function resizeModalBox() {
    setTimeout(
        function () {

            const modal = document.getElementById('layouteditor_modalbox');

            const h = window.innerHeight;
            const rect = modal.getBoundingClientRect();

            let content_height;
            let modalBoxHeightChanged = false;
            if (rect.bottom > h - 100) {
                content_height = h - 150;
                modal.style.top = "50px";
                modal.style.height = content_height + "px";

                const content = document.getElementById('layouteditor_tagsContent0');
                if (content)
                    content.style.height = (h - 250) + "px";

                modalBoxHeightChanged = true;
            } else
                content_height = rect.bottom - rect.top;

            if (modalBoxHeightChanged) {
                const contentbox_rect = modal.getBoundingClientRect();
                let contentbox = document.getElementById('modalParamList');
                if (contentbox) {
                    contentbox.style.height = (content_height - contentbox_rect.top - 30 - 120) + "px";
                }

                contentbox = document.getElementById('layouteditor_fields');
                if (contentbox) {
                    contentbox.style.height = (content_height - contentbox_rect.top - 30 - 10) + "px";
                }


            }

            const box = document.getElementById("layouteditor_modalbox");
            box.style.visibility = "visible";

        }, 100);

    return true;
}

function showModal() {
    // Get the modal

    const modal = document.getElementById('layouteditor_Modal');

    // Get the <span> element that closes the modal
    const span = document.getElementsByClassName("layouteditor_close")[0];

    // When the user clicks on <span> (x), close the modal
    span.onclick = function () {
        modal.style.display = "none";
        let cm = getActiveEditor();
        cm.focus();
    };

    // When the user clicks anywhere outside the modal, close it
    window.onclick = function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
            const cm = getActiveEditor();
            cm.focus();
        }
    };

    const box = document.getElementById("layouteditor_modalbox");
    box.style.visibility = "hidden";
    box.style.height = "auto";

    modal.style.display = "block";

    let e = document.documentElement;

    const doc_w = e.clientWidth;
    const doc_h = e.clientHeight;

    const w = box.offsetWidth;
    const h = box.offsetHeight;

    let x = (doc_w / 2) - w / 2;
    if (x < 10)
        x = 10;

    if (x + w + 10 > doc_w)
        x = doc_w - w - 10;

    let y = (doc_h / 2) - h / 2;

    if (y < 50)
        y = 50;

    if (y + h + 50 > doc_h) {
        y = doc_h - h - 50;
    }

    box.style.left = x + 'px';
    box.style.top = y + 'px';

    resizeModalBox();
}

function showModalForm(tagStartChar, postfix, tagEndChar, tag, top, left, line, positions, isNew) {
    //detect tag type first

    if (tagStartChar === '{') {
        //Old style
        showModalTagForm(tagStartChar, postfix, tagEndChar, tag, top, left, line, positions, isNew);
    } else if (tagStartChar === '{{') {
        //Twig tag
        let tag_pair = parseQuote(tag, ['.'], false);
        let twigClass = tag_pair[0].trim();

        let twigClasses = ['fields', 'user', 'url', 'html', 'document', 'record', 'records', 'plugins', 'table', 'tables'];
        if (twigClasses.indexOf(twigClass) !== -1) {
            showModalTagForm('{{', postfix, '}}', tag.trim(), top, left, line, positions, isNew);
        } else if (tag_pair.length > 1) {
            postfix = '';
            if (tag_pair.length > 2)
                postfix = tag_pair[1];

            showModalFieldTagForm('[', postfix, ']', tag.trim(), top, left, line, positions, isNew);
        } else {
            postfix = '';
            showModalFieldTagForm('[', postfix, ']', tag.trim(), top, left, line, positions, isNew);
        }
    } else if (tagStartChar === '[') {
        let tag_pair = parseQuote(tag, [':', '='], false);

        if (tag_pair[0] === "_if" || tag_pair[0] === "_endif") {
            showModalTagForm('[', postfix, ']', tag, top, left, line, positions, isNew);
        } else if (tag_pair[0] === "_value" || tag_pair[0] === "_edit") {
            if (tag_pair[0] === "_value")
                postfix = '.value';
            else if (tag_pair[0] === "_edit")
                postfix = '.edit';

            let clean_tag = tag_pair[1];

            if (tag_pair.length === 3)
                postfix += '("' + tag_pair[2] + '")';

            showModalFieldTagForm(tagStartChar, postfix, tagEndChar, clean_tag, top, left, line, positions, isNew);
        } else {
            if (current_layout_type === 2)
                postfix = '.edit';

            showModalFieldTagForm(tagStartChar, postfix, tagEndChar, tag, top, left, line, positions, isNew);
        }
    } else {
        showModalFieldTagForm(tagStartChar, postfix, tagEndChar, tag, top, left, line, positions, isNew);
    }
}

function findTagParameter(tag) {

    let pos1 = tag.indexOf("(");
    let pos2 = tag.lastIndexOf(")");

    return tag.substring(pos1 + 1, pos2);
}

function safeOld2NewParamConversion(old) {

    let TempPairList1 = parseQuote(old, [':'], false);
    let TempPairList2 = [];

    for (let i = 0; i < TempPairList1.length; i++) {
        let v = TempPairList1[i];
        if ((isNaN(v) && v !== 'true' && v !== 'false') || v === '') {
            v = '"' + v + '"';
        }
        TempPairList2.push(v);
    }
    return TempPairList2.join(",");
}

function showModalTagForm(tagStartChar, postfix, tagEndChar, tag, top, left, line, positions, isNew) {
    let paramValueString = "";

    if (tagStartChar === '{{') {
        let tag_pair = parseQuote(tag, ['('], false);
        temp_params_tag = tag_pair[0].trim();

        paramValueString = findTagParameter(tag);
    } else {
        let tag_pair = parseQuote(tag, [':', '='], false);
        temp_params_tag = tag_pair[0].trim();

        if (tag_pair.length > 1) {
            let pos1 = tag.indexOf(":");
            paramValueString = tag.substring(pos1 + 1, tag.length);
        }
    }

    let tagObject = findTagObjectByName(tagStartChar, tagEndChar, temp_params_tag);

    if (tagObject == null || typeof tagObject !== 'object')
        return null;

    let param_array = getParamOptions(tagObject.params, 'param');
    let param_att = tagObject["@attributes"];

    if (tagStartChar === '{') {
        if (typeof (param_att.twigsimplereplacement) !== "undefined" && param_att.twigsimplereplacement !== "") {
            let cursor_from = {line: line, ch: positions[0]};
            let cursor_to = {line: line, ch: positions[1]};
            let editor = getActiveEditor();//codemirror_editors[codemirror_active_index];
            let doc = editor.getDoc();
            doc.replaceRange(param_att.twigsimplereplacement, cursor_from, cursor_to, "");
            return true;
        } else if (typeof (param_att.twigreplacestartchar) !== "undefined" && param_att.twigreplacestartchar !== ""
            && typeof (param_att.twigreplaceendchar) !== "undefined" && param_att.twigreplaceendchar !== "") {
            let cursor_from = {line: line, ch: positions[0]};
            let cursor_to = {line: line, ch: positions[1]};

            paramValueString = safeOld2NewParamConversion(paramValueString);

            let result = param_att.twigreplacestartchar + paramValueString + param_att.twigreplaceendchar;
            let editor = getActiveEditor();//codemirror_editors[codemirror_active_index];
            let doc = editor.getDoc();
            doc.replaceRange(result, cursor_from, cursor_to, "");
            return true;
        } else if (typeof (param_att.twigreplacement) !== "undefined" && param_att.twigreplacement !== "") {

            if (typeof (param_att.twigmapname) !== "undefined" && param_att.twigmapname !== "") {

                if (typeof (param_att.twigmapparam) !== "undefined" && param_att.twigmapparam !== "") {

                    for (let i3 = 0; i3 < param_array.length; i3++) {
                        let tmpPar = param_array[i3]["@attributes"];
                        if (tmpPar.name === param_att.twigmapname) {
                            let option_array = getParamOptions(param_array[i3], 'option');
                            let tmlLstOfParams = paramValueString.split(",");

                            for (let i4 = 0; i4 < option_array.length; i4++) {
                                let tmpAtt = option_array[i4]["@attributes"];

                                if (tmpAtt.value === tmlLstOfParams[0]) {
                                    temp_params_tag = param_att.twigreplacement + '.' + tmpAtt.twigexactreplacement;

                                    if (tmlLstOfParams.length > 1)
                                        paramValueString = safeOld2NewParamConversion(tmlLstOfParams[1]);
                                    else
                                        paramValueString = '';
                                }
                            }
                            break;
                        }
                    }
                }

            } else if (typeof (param_att.twigoptionsreplacement) !== "undefined" && param_att.twigoptionsreplacement === "1") {
                temp_params_tag = param_att.twigreplacement + '.' + paramValueString;
                paramValueString = '';
            } else
                temp_params_tag = param_att.twigreplacement;

            if (typeof (param_att.twigreplacementparams) !== "undefined" && param_att.twigreplacementparams !== "")
                paramValueString = param_att.twigreplacementparams;

            tagObject = findTagObjectByName('{{', '}}', temp_params_tag);
            param_array = getParamOptions(tagObject.params, 'param');
            param_att = tagObject["@attributes"];
        } else if (typeof (param_att.twigclass) !== "undefined" && param_att.twigclass !== "")
            temp_params_tag = param_att.twigclass + '.' + temp_params_tag;

    } else if (tagStartChar === '[') {
        if (typeof (param_att.twigreplacestartchar) !== "undefined" && param_att.twigreplacestartchar !== "" && typeof (param_att.twigreplaceendchar) !== "undefined" && param_att.twigreplaceendchar !== "") {
            let cursor_from = {line: line, ch: positions[0]};
            let cursor_to = {line: line, ch: positions[1]};
            let result = param_att.twigreplacestartchar + paramValueString + param_att.twigreplaceendchar;
            let editor = getActiveEditor();//codemirror_editors[codemirror_active_index];
            let doc = editor.getDoc();
            doc.replaceRange(result, cursor_from, cursor_to, "");
            return true;
        } else if (typeof (param_att.twigsimplereplacement) !== "undefined" && param_att.twigsimplereplacement !== "") {
            let cursor_from = {line: line, ch: positions[0]};
            let cursor_to = {line: line, ch: positions[1]};
            let editor = getActiveEditor();//codemirror_editors[codemirror_active_index];
            let doc = editor.getDoc();
            doc.replaceRange(param_att.twigsimplereplacement, cursor_from, cursor_to, "");
            return true;
        }

        return false;
    }

    let countParams = param_array.length;
    if (typeof (param_att.repetitive) !== "undefined" && param_att.repetitive === "1" && param_array.length === 1)
        countParams = -1;//unlimited number of parameters

    let form_content = getParamEditForm(tagObject, line, positions, isNew, countParams, '{{ ', postfix, ' }}', paramValueString, []);

    if (form_content == null)
        return false;

    const obj = document.getElementById("layouteditor_modal_content_box");
    obj.innerHTML = form_content;

    if (joomlaVersion < 4) {
        jQuery(function ($) {
            //container ||
            $(obj).find(".hasPopover").popover({
                "html": true,
                "trigger": "hover focus",
                "layouteditor_modal_content_box": "body"
            });
        });
    }
    updateParamString("fieldtype_param_", 1, countParams, "current_tagparameter", null, false, false, tagStartChar);
    showModal();
}

//Used in onchange event
function addTag(tagstartchar, tagendchar, tag, param_count) {

    let postfix = '';
    let cm = getActiveEditor();//codemirror_editors[0];

    if (param_count > 0) {

        let tagname = atob(tag);
        let cr = cm.getCursor();
        let positions = [cr.ch, cr.ch];
        let mousepos = cm.cursorCoords(cr, "window");
        showModalTagForm(tagstartchar.trim(), postfix, tagendchar.trim(), tagname, mousepos.top, mousepos.left, cr.line, positions, 1);
    } else {
        updateCodeMirror(tagstartchar + atob(tag) + tagendchar);
        document.getElementById('layouteditor_Modal').style.display = "none";
        cm.focus();
    }
}

function updateCodeMirror(text) {
    let cm = getActiveEditor();
    const doc = cm.getDoc();
    const cursor = cm.getCursor();
    doc.replaceRange(text, cursor);
}

function textarea_findindex(code) {
    for (let i = 0; i < text_areas.length; i++) {
        let a = text_areas[i][0];

        if (a === 'jform_' + code)
            return text_areas[i][1];
    }
    return -1;
}

function findTagInMultiline(cm, ch, startLine) {

    let start_pos = [-1, -1];
    let level = 1;
    let str = cm.getLine(startLine);
    start_pos[1] = startLine;

    for (let i = ch; i > -1; i--) {

        if ((str[i] === ']' || str[i] === '}') && i !== ch)
            level++;

        if (str[i] === '[') {

            level--;
            if (level === 0) {
                start_pos[0] = i;
                break;
            }
        } else if (str[i] === '{') {

            level--;
            if (level === 0) {

                if (i > 0 && str[i - 1] === '{') {
                    start_pos[0] = i - 1;
                    break;
                }

                start_pos[0] = i;
                break;
            }
        }
    }

    if (start_pos[0] === -1)
        return null;

    return findTagInMultilineMoveRight(cm, start_pos);
}

function findTagInMultilineMoveRight(cm, start_pos) {
    let end_pos = [-1, -1];
    let lines = cm.lineCount();
    let levelList = [];
    let i2 = start_pos[0];
    let tagString = '';
    let startLine = start_pos[1];
    let str = cm.getLine(startLine);

    while (1) {

        tagString += str[i2];

        if (str[i2] === '[') {
            levelList.push('[');
        } else if (str[i2] === ']') {
            if (levelList.length === 0 || levelList[levelList.length - 1] !== '[') {
                alert('Syntax error. Closing "]" appeared before opening "[".');
                return null;
            }
            levelList = levelList.slice(0, -1);
        } else if (str[i2] === '{') {
            levelList.push('{');
        } else if (str[i2] === '}') {
            if (levelList.length === 0 || levelList[levelList.length - 1] !== '{') {
                alert('Syntax error. Closing "}" appeared before opening "{".');
                return null;
            }
            levelList = levelList.slice(0, -1);
        } else if (str[i2] === '"') {
            if (levelList.length === 0 || levelList[levelList.length - 1] !== '"')
                levelList.push('"');
            else
                levelList = levelList.slice(0, -1);

        } else if (str[i2] === "'") {
            if (levelList.length === 0 || levelList[levelList.length - 1] !== "'")
                levelList.push("'");
            else
                levelList = levelList.slice(0, -1);
        }
        if (levelList.length === 0) {
            end_pos = [i2, startLine];
            break;
        }

        i2++;

        if (i2 > str.length - 1) {

            startLine += 1;
            if (startLine === lines)
                break;

            tagString += '\n';

            str = cm.getLine(startLine);
            i2 = 0;
        }
    }

    if (end_pos[1] === -1)
        return null;

    return [start_pos, end_pos, tagString];
}

function findTagInLine(ch, str) {

    let start_pos = -1;
    let end_pos = -1;
    let level = 1;
    let startChar = '';
    let endChar = '';

    for (let i = ch; i > -1; i--) {

        if ((str[i] === ']' || str[i] === '}') && i !== ch)
            level++;

        if (str[i] === '[' || str[i] === '{') {

            if (startChar === '')
                startChar = str[i];

            level--;
            if (level === 0) {
                start_pos = i;
                break;
            }
        }
    }

    if (start_pos === -1)
        return null;

    level = 1;
    for (let i2 = ch; i2 < str.length; i2++) {

        if (str[i2] === '[' || str[i2] === '{')
            level++;

        if (str[i2] === ']' || str[i2] === '}') {

            if (endChar === '')
                endChar = str[i2];

            level--;
            if (level === 0) {
                end_pos = i2;
                break;
            }
        }
    }

    if (end_pos === -1)
        return null;

    if (start_pos <= ch && end_pos >= ch)
        return [start_pos, end_pos + 1];// +1 because position should end after the tag

    return null;
}

function findTagObjectByName(tagStartChar, tagEndChar, lookForTag) {

    let s;
    let TwigTag = false;

    if (tagStartChar === '{{') {
        tagStartChar = '{';
        tagEndChar = '}'

        TwigTag = true;
    }

    for (s = 0; s < tagsets.length; s++) {
        let tagSet = tagsets[s];
        let tags = getParamOptions(tagSet, 'tag');

        for (let i = 0; i < tags.length; i++) {
            let tag = tags[i];
            let a = tag["@attributes"];

            if (lookForTag.indexOf(".") === -1) {

                //Conversion - OLD to Twig
                if (typeof (a.twigclass) == "undefined" || a.twigclass === "") {

                    if (a.name === lookForTag) {
                        if (typeof (a.startchar) !== "undefined" && a.startchar === tagStartChar && a.endchar === tagEndChar)
                            return tag;
                    }
                }
            } else if (TwigTag && typeof (a.twigclass) !== "undefined" && a.twigclass !== "") {

                //Twig Tag
                if (a.twigclass + '.' + a.name === lookForTag && a.startchar === tagStartChar && a.endchar === tagEndChar)
                    return tag;
            }
        }
    }

    //If nothing found then simplify the search
    for (s = 0; s < tagsets.length; s++) {
        let tagSet = tagsets[s];
        let tags = getParamOptions(tagSet, 'tag');

        for (let i = 0; i < tags.length; i++) {
            let tag = tags[i];
            let a = tag["@attributes"];

            if (lookForTag.indexOf(".") === -1) {

                //Conversion - OLD to Twig
                if (a.name === lookForTag && a.startchar === tagStartChar && a.endchar === tagEndChar)
                    return tag;

            } else if (TwigTag && typeof (a.twigclass) !== "undefined" && a.twigclass !== "") {

                //Twig Tag
                if (a.twigclass + '.' + a.name === lookForTag && a.startchar === tagStartChar && a.endchar === tagEndChar)
                    return tag;
            }
        }
    }
    return null;
}

function getParamEditForm(tagObject, line_number, positions, isNew, countParams, tagStartChar, postfix, tagEndChar, paramValueString, fieldTypeParametersList) {

    let result = renderParamBox(tagObject, "current_tagparameter", paramValueString, fieldTypeParametersList);
    result += '<div class="dynamic_values"><span class="dynamic_values_label">Tag with parameter:</span> ';
    result += tagStartChar;
    result += temp_params_tag;

    let paramValueStringTemp = paramValueString.replaceAll('<', '&lt;');
    paramValueStringTemp = paramValueStringTemp.replaceAll('>', '&gt;');

    result += postfix + '(<span id="current_tagparameter">' + paramValueStringTemp + '</span>)';
    result += tagEndChar + '</div>';
    result += '<div style="text-align:center;">';

    let postfixClean = postfix.replaceAll('"', '****quote****');
    result += '<button id="clsave" onclick=\'return saveParams(event,' + countParams + ',' + line_number + ',' + positions[0] + ',' + positions[1] + ',' + isNew + ',"' + tagStartChar + '","' + tagEndChar + '","' + postfixClean + '");\' class="btn btn-small button-apply btn-success">Save</button>';
    result += ' <button id="clclose" onclick=\'return closeModal(event);\' class="btn btn-small button-cancel btn-danger">Cancel</button>';
    result += '</div>';
    return result;
}

function saveParams(e, countParams, line_number, pos1, pos2, isNew, tagStartChar, tagEndChar, postfixClean) {

    let postfix = postfixClean.replaceAll('****quote****', '"');

    updateParamString("fieldtype_param_", 1, countParams, "current_tagparameter", null, false);
    e.preventDefault();

    let tmp_params = document.getElementById('current_tagparameter').innerHTML;

    let paramValueStringTemp = temp_params_tag.replaceAll('<', '&lt;');
    paramValueStringTemp = paramValueStringTemp.replaceAll('>', '&gt;');

    let result = tagStartChar + paramValueStringTemp + postfix;

    if (tmp_params !== "")
        result += '(' + tmp_params + ')';//{{ tag.edit(par1,par2) }} where ".edit" is the postfix

    result += tagEndChar;

    let cursor_from = {line: line_number, ch: pos1};
    let cursor_to = {line: line_number, ch: pos2};
    let editor = getActiveEditor();
    let doc = editor.getDoc();
    doc.replaceRange(result, cursor_from, cursor_to, "");
    document.getElementById('layouteditor_Modal').style.display = "none";
    editor.focus();
    return false;
}

function closeModal(e) {
    e.preventDefault();

    document.getElementById('layouteditor_Modal').style.display = "none";
    const cm = getActiveEditor();//codemirror_editors[0];
    cm.focus();
    return false;
}

function define_cmLayoutEditor() {
    define_cmLayoutEditor1('layouteditor', 'text/html');
}

//Used in layouteditor/php
function define_cmLayoutEditor1(modeName, nextModeName) {
    CodeMirror.defineMode(modeName, function (config, parserConfig) {
        const layoutEditorOverlay =
            {
                token: function (stream, state) {
                    if (stream.match("[")) {
                        let hasParameters = false;
                        let level = 1;
                        let ch = "";
                        while ((ch = stream.next()) != null) {
                            if (ch === "[") {
                                level++;
                            }

                            if (ch === "]") {
                                level -= 1;
                                if (level === 0) {
                                    stream.eat("]");

                                    if (hasParameters)
                                        return "ct_tag_withparams";
                                    else
                                        return "ct_tag";
                                }
                            }

                            if (ch === ':' && level === 1) {
                                hasParameters = true;
                            }
                        }
                    } else if (stream.match("{")) {


                        let hasParameters2 = false;
                        let level2 = 1;
                        let ch2 = "";
                        while ((ch2 = stream.next()) != null) {
                            if (ch2 === "{") {
                                level2++;
                            }

                            if (ch2 === "}") {
                                level2 -= 1;
                                if (level2 === 0) {
                                    stream.eat("}");

                                    if (hasParameters2)
                                        return "ct_curvy_tag_withparams";
                                    else
                                        return "ct_curvy_tag";
                                }
                            }

                            if (ch2 === ':' && 2 === 1) {
                                hasParameters2 = true;
                            }
                        }
                    }
                    while (stream.next() != null && !(stream.match("[", false) || stream.match("{", false))) {
                    }//|| stream.match("{")
                    return null;
                }
            };

        return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || nextModeName), layoutEditorOverlay);
    });
}

function do_render_current_TagSets() {

    if (type_obj === null)
        return;

    layouttypeid = type_obj.value;

    let index = 0;
    let tabs = [];

    for (let i = 0; i < tagsets.length; i++) {
        let tagSet = tagsets[i];
        let a = tagSet["@attributes"];

        if (typeof (a.deprecated) == "undefined" || a.deprecated === "0") {

            tabs.push({
                'id': 'layouteditor_tags' + index + '_' + i + '', 'title': a.label,
                'content': '<p>' + a.description + '</p>' + renderTags(index, tagSet)
            });
        }
    }

    if (tabs.length > 0)
        return renderTabs('layouteditor_fields', tabs);
    else
        return '<div class="FieldTagWizard"><p>No Tags available for this Layout Type</p></div>';
}

function renderTags(index, tagSet) {

    let tagSetAttributes = tagSet["@attributes"];

    let tags = getParamOptions(tagSet, 'tag');
    let result = '<div class="dynamic_values" style="padding-left:0 !important;">';

    let buttonClass = "";
    let buttonClassPro = "";


    if (typeof Joomla !== 'undefined') {
        buttonClass = "btn-primary";
        buttonClassPro = "btn-default";
    } else if (document.body.classList.contains('wp-admin') || document.querySelector('#wpadminbar')) {
        buttonClass = "button button-primary";
        buttonClassPro = "button";
    }

    for (let i = 0; i < tags.length; i++) {
        let tag_object = tags[i];
        let tag = tag_object["@attributes"];

        if (typeof (tag.deprecated) == "undefined" || tag.deprecated === "0") {
            let t = "";
            let params = getParamOptions(tag_object.params, 'param');

            let fullTagName = '';
            if (typeof (tag.twigclass) !== "undefined" && tag.twigclass !== "")
                fullTagName = tag.twigclass + '.' + tag.name;
            else
                fullTagName = tag.name;

            if (tagSetAttributes.name === 'filters')
                fullTagName = tag.examplevalue + ' | ' + fullTagName;


            let isOk = true;

            if (typeof (tag.layouttypes) != "undefined" && tag.deprecated !== "") {
                let layoutTypes = tag.layouttypes.split(",");
                isOk = layoutTypes.includes(current_layout_type + "");
            }

            if (isOk) {
                if (params.length === 0)
                    t = '{{ ' + fullTagName + ' }}';
                else
                    t = '{{ ' + fullTagName + '(<span>Params</span>)' + ' }}';

                if (window.Joomla instanceof Object || (typeof (tag.wordpress) !== "undefined" & tag.wordpress === "true")) {
                    result += '<div style="vertical-align:top;display:inline-block;">';

                    if (proversion || typeof (tag.proversion) === "undefined" || parseInt(tag.proversion) !== 1) {
                        result += '<a href=\'javascript:addTag("{{ "," }}","' + btoa(fullTagName) + '",' + params.length + ');\' class="' + buttonClass + '" title="' + tag.description + '">' + t + '</a> ';
                    } else {
                        result += '<div style="display:inline-block;"><div class="' + buttonClassPro + '" title="' + tag.description + '">' + t + ' *</div></div> ';
                    }
                    result += '</div>';
                }
            }
        }
    }

    result += '</div>';

    if (!proversion) {
        if (window.Joomla instanceof Object)
            result += '<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">* Get Custom Tables PRO Version</a></div>';
        else
            result += '<div class="ct_doc_pro_label"><a href="https://ct4.us" target="_blank">* Get Custom Tables PRO Version</a></div>';
    }

    return result;
}

function addTabExtraEvents3() {
    //let layoutcode_textarea = document.getElementById('jform_layoutcode');
    //window.location.href = "#layoutcode-tab";
    //layoutcode-tab

    jQuery(function ($) {
        $(".nav-tabs a").click(function (e) {
            let a = e.target.href;
            let codePair = a.split("#");
            let code = codePair[1].replace('-tab', '');
            const index = textarea_findindex(code);

            if (index !== -1) {
                setTimeout(function () {
                    codemirror_active_index = index;
                    codemirror_active_areatext_id = 'jform_' + code;
                    let cm = getActiveEditor();//codemirror_editors[index];
                    cm.refresh();
                    adjustEditorHeight();
                }, 100);
            }
        });
    });
}

function addTabExtraEvent4(id) {
    let tab_object = document.querySelectorAll('[aria-controls="' + id + '-tab"]');

    for (let i = 0; i < tab_object.length; i++) {
        tab_object[i].addEventListener("click", function () {
            let index = textarea_findindex(id);

            setTimeout(function () {
                codemirror_active_index = index;
                codemirror_active_areatext_id = 'jform_' + id;
                let cm = getActiveEditor();//codemirror_editors[index];
                cm.refresh();
                adjustEditorHeight();
            }, 100);
        });
    }
}

//Used in layouteditor.php
function addTabExtraEvents() {

    let tabs = ['layoutcode', 'layoutmobile', 'layoutcss', 'layoutjs']

    if (joomlaVersion < 4) {
        addTabExtraEvents3();
    } else {

        /*
        setTimeout(function(){
            codemirror_active_index=0;
            let cm=codemirror_editors[0];
            cm.refresh();
        }, 100);*/

        for (let i = 0; i < tabs.length; i++)
            addTabExtraEvent4(tabs[i]);
    }

    let index = 0;
    codemirror_active_index = index;
    codemirror_active_areatext_id = 'jform_' + tabs[0];
    let cm = getActiveEditor();//codemirror_editors[index];

    if (typeof (cm) == "undefined")
        return;

    cm.refresh();
}

//Used in layouteditor.php
function addExtraEvents() {

    setTimeout(function () {
        let editors = document.getElementsByClassName("CodeMirror");

        for (let i = 0; i < editors.length; i++)
            addExtraEvent(i);
    }, 500);
}

function addExtraEvent(index) {

    let cm = codemirror_editors[index];
    cm.refresh();

    cm.on('dblclick', function () {
        //cm.state
        let cr = null;
        let line = null;
        cr = cm.getCursor();
        line = cm.getLine(cr.line);
        doExtraCodeMirrorEvent(cr.ch, line, cr.line, cm.cursorCoords(cr, "window"));
    }, true);
}

function doExtraCodeMirrorEvent(ch, lineString, lineNumber, mousePos) {
    let positions = findTagInLine(ch, lineString);

    if (positions != null) {
        let startChar = lineString.substring(positions[0], positions[0] + 1); //+1 to have 1 character
        if (startChar === '{') {
            let startChar2 = lineString.substring(positions[0] - 1, positions[0] + 1);
            if (startChar2 === '{{')
                startChar = '{{';
        }

        let endChar = lineString.substring(positions[1] - 1, positions[1] - 1 + 1);//-1 because position ends after the tag
        if (endChar === '}') {
            let endChar2 = lineString.substring(positions[1] - 1, positions[1] - 1 + 2);
            if (endChar2 === '}}')
                endChar = '}}';
        }

        let tag = lineString.substring(positions[0] + 1, positions[1] - 1);//-1 because position ends after the tag

        if (startChar === '{{') {
            positions[0] = positions[0] - 1;
            positions[1] = positions[1] + 1;
        }

        let postfix = ''; //todo

        showModalForm(startChar, postfix, endChar, tag, mousePos.top, mousePos.left, lineNumber, positions, 0);
    } else {
        let cm = getActiveEditor();
        let cr = cm.getCursor();
        let positionsRange = findTagInMultiline(cm, cr.ch, cr.line);
        if (positionsRange !== null) {

            convertOldSimpleCatalogToNew(cm, positionsRange);
        } else {
            alert("The tag has a syntax error or too complex for me to understand.")
        }
    }
}

function convertOldSimpleCatalogToNew(cm, positionsRange) {

    let tagTemp = splitQuoteSafe(positionsRange[2], ';', '"', true);
    let tag = splitQuoteSafe(tagTemp[0], ',', '"', true);
    let tagName = '';
    let headValues = [];
    let bodyValues = [];
    let i;

    for (i = 0; i < tag.length; i += 1) {
        let tagPair = splitQuoteSafe(tag[i], ':', '"', false);

        if (i == 0) {
            tagName = tagPair[0];
            headValues.push(tagPair[1].replaceAll('\n', ''));
            bodyValues.push(tagPair[2].replaceAll('\n', ''));
        } else {
            headValues.push(tagPair[0].replaceAll('\n', ''));
            bodyValues.push(tagPair[1].replaceAll('\n', ''));
        }
    }

    if (tagName !== '{catalogtable') {
        alert('Unsupported tag or its a multiline tag.')
        return null;
    }

    let str = '\n<table>\n';
    str += '<thead><tr>\n';
    for (i = 0; i < headValues.length; i++) {
        str += "<th>" + headValues[i] + "</th>\n";
    }
    str += "</tr></thead>\n";

    str += '<tbody>\n{% block record %}\n<tr>\n';
    for (i = 0; i < bodyValues.length; i++) {
        str += '<td>' + bodyValues[i] + '</td>\n';
    }
    str += '</tr>\n{% endblock %}\n</tbody>\n';

    str += '</table>\n';
    // get the entire editor text from CodeMirror editor
    let text = cm.getValue();

    // edit the text, for example
    text = text.replace(positionsRange[2], str);

    // set the text back to the editor
    cm.setValue(text);
}

function splitQuoteSafe(str, delimiter, quote, preserveQuotes) {

    let list = [];
    let levelList = [];
    let i = 0;
    let tempString = '';

    while (1) {

        if (str[i] === quote) {
            if (levelList.length === 0 || levelList[levelList.length - 1] !== quote)
                levelList.push(quote);
            else
                levelList = levelList.slice(0, -1);

            if (preserveQuotes)
                tempString += str[i];
        } else {
            if (levelList.length === 0 && str[i] === delimiter) {
                list.push(tempString);
                tempString = '';
            } else
                tempString += str[i];
        }

        i++;

        if (i > str.length - 1)
            break;
    }

    if (tempString !== '')
        list.push(tempString);

    return list;
}

function adjustEditorHeight() {
    let editors = document.getElementsByClassName("CodeMirror");
    if (editors.length === 0)
        return false;//editor not found

    for (let i = 0; i < editors.length; i++) {
        let editor = editors[i];
        let h = window.innerHeight;
        let rect = editor.getBoundingClientRect();
        let editorHeight = h - rect.top - 40;

        let layoutFilePathDivs = document.getElementsByClassName("layoutFilePath");
        if (layoutFilePathDivs.length > 0)
            editorHeight -= 15;

        if (joomlaVersion < 4)
            editorHeight -= 5;
        else
            editorHeight += 20;

        editor.style.height = editorHeight + 'px';
        let cm = codemirror_editors[i];
        if (cm && cm.codemirror)
            cm.codemirror.refresh();
    }
}
