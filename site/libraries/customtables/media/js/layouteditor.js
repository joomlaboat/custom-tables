/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/js/layouteditor.js
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
const codemirror_editors = [];
let codemirror_active_index = 0;
let codemirror_active_areatext_id = null;
let temp_params_tag = "";
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

    let parts = location.href.split("/administrator/");
    const url = parts[0] + '/index.php?option=com_customtables&view=xml&xmlfile=tags';

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
        const cm = codemirror_editors[0];
        cm.focus();
    };

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
            const cm = codemirror_editors[0];
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

function showModalTagForm(tagstartchar, postfix, tagendchar, tag, top, left, line, positions, isnew) {
    let paramvaluestring = "";

    if (tagstartchar === '{{') {
        let tag_pair = parseQuote(tag, ['('], false);
        temp_params_tag = tag_pair[0].trim();

        paramvaluestring = findTagParameter(tag);
    } else {
        let tag_pair = parseQuote(tag, [':', '='], false);
        temp_params_tag = tag_pair[0].trim();

        if (tag_pair.length > 1) {
            let pos1 = tag.indexOf(":");
            paramvaluestring = tag.substring(pos1 + 1, tag.length);
        }
    }

    let tagobject = findTagObjectByName(tagstartchar, tagendchar, temp_params_tag);

    if (tagobject == null || typeof tagobject !== 'object')
        return null;

    let param_array = getParamOptions(tagobject.params, 'param');
    let param_att = tagobject["@attributes"];

    if (tagstartchar === '{') {
        if (typeof (param_att.twigsimplereplacement) !== "undefined" && param_att.twigsimplereplacement !== "") {
            let cursor_from = {line: line, ch: positions[0]};
            let cursor_to = {line: line, ch: positions[1]};
            let editor = codemirror_editors[codemirror_active_index];
            let doc = editor.getDoc();
            doc.replaceRange(param_att.twigsimplereplacement, cursor_from, cursor_to, "");
            return true;
        } else if (typeof (param_att.twigreplacestartchar) !== "undefined" && param_att.twigreplacestartchar !== ""
            && typeof (param_att.twigreplaceendchar) !== "undefined" && param_att.twigreplaceendchar !== "") {
            let cursor_from = {line: line, ch: positions[0]};
            let cursor_to = {line: line, ch: positions[1]};

            paramvaluestring = safeOld2NewParamConversion(paramvaluestring);

            let result = param_att.twigreplacestartchar + paramvaluestring + param_att.twigreplaceendchar;
            let editor = codemirror_editors[codemirror_active_index];
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
                            let tmlLstOfParams = paramvaluestring.split(",");

                            for (let i4 = 0; i4 < option_array.length; i4++) {
                                let tmpAtt = option_array[i4]["@attributes"];

                                if (tmpAtt.value === tmlLstOfParams[0]) {
                                    temp_params_tag = param_att.twigreplacement + '.' + tmpAtt.twigexactreplacement;

                                    if (tmlLstOfParams.length > 1)
                                        paramvaluestring = safeOld2NewParamConversion(tmlLstOfParams[1]);
                                    else
                                        paramvaluestring = '';
                                }
                            }
                            break;
                        }
                    }
                }

            } else if (typeof (param_att.twigoptionsreplacement) !== "undefined" && param_att.twigoptionsreplacement === "1") {
                temp_params_tag = param_att.twigreplacement + '.' + paramvaluestring;
                paramvaluestring = '';
            } else
                temp_params_tag = param_att.twigreplacement;

            if (typeof (param_att.twigreplacementparams) !== "undefined" && param_att.twigreplacementparams !== "") {
                paramvaluestring = param_att.twigreplacementparams;
            }

            tagobject = findTagObjectByName('{{', '}}', temp_params_tag);
            //tagobject = findTagObjectByName(tagstartchar,tagendchar,temp_params_tag);

            param_array = getParamOptions(tagobject.params, 'param');
            param_att = tagobject["@attributes"];
        } else if (typeof (param_att.twigclass) !== "undefined" && param_att.twigclass !== "")
            temp_params_tag = param_att.twigclass + '.' + temp_params_tag;

    } else if (tagstartchar === '[') {
        if (typeof (param_att.twigreplacestartchar) !== "undefined" && param_att.twigreplacestartchar !== "" && typeof (param_att.twigreplaceendchar) !== "undefined" && param_att.twigreplaceendchar !== "") {
            let cursor_from = {line: line, ch: positions[0]};
            let cursor_to = {line: line, ch: positions[1]};
            let result = param_att.twigreplacestartchar + paramvaluestring + param_att.twigreplaceendchar;
            let editor = codemirror_editors[codemirror_active_index];
            let doc = editor.getDoc();
            doc.replaceRange(result, cursor_from, cursor_to, "");
            return true;
        } else if (typeof (param_att.twigsimplereplacement) !== "undefined" && param_att.twigsimplereplacement !== "") {
            let cursor_from = {line: line, ch: positions[0]};
            let cursor_to = {line: line, ch: positions[1]};
            let editor = codemirror_editors[codemirror_active_index];
            let doc = editor.getDoc();
            doc.replaceRange(param_att.twigsimplereplacement, cursor_from, cursor_to, "");
            return true;
        }

        return false;
    }

    let countparams = param_array.length;
    if (typeof (param_att.repeatative) !== "undefined" && param_att.repeatative === "1" && param_array.length === 1)
        countparams = -1;//unlimited number of parameters

    let form_content = getParamEditForm(tagobject, line, positions, isnew, countparams, '{{ ', postfix, ' }}', paramvaluestring);

    if (form_content == null) {
        return false;
    }

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

    updateParamString("fieldtype_param_", 1, countparams, "current_tagparameter", null, false, false, tagstartchar);

    showModal();
}

//Used in onchange event
function addTag(tagstartchar, tagendchar, tag, param_count) {

    let postfix = '';
    let cm = codemirror_editors[0];

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
    const editor = codemirror_editors[codemirror_active_index];

    const doc = editor.getDoc();
    const cursor = doc.getCursor();
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

function findTagInLine(ch, str) {

    let start_pos = -1;
    let end_pos = -1;
    let level = 1;
    let startchar = '';
    let endchar = '';

    for (let i = ch; i > -1; i--) {

        if ((str[i] === ']' || str[i] === '}') && i !== ch)
            level++;

        if (str[i] === '[' || str[i] === '{') {

            if (startchar === '')
                startchar = str[i];

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

            if (endchar === '')
                endchar = str[i2];

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

function findTagObjectByName(tagstartchar, tagendchar, lookfor_tag) {

    let s;
    let TwigTag = false;

    if (tagstartchar === '{{') {
        tagstartchar = '{';
        tagendchar = '}'

        TwigTag = true;
    }

    for (s = 0; s < tagsets.length; s++) {
        let tagset = tagsets[s];
        let tags = getParamOptions(tagset, 'tag');

        for (let i = 0; i < tags.length; i++) {
            let tag = tags[i];
            let a = tag["@attributes"];

            if (lookfor_tag.indexOf(".") === -1) {

                //Conversion - OLD to Twig
                if (typeof (a.twigclass) == "undefined" || a.twigclass === "") {

                    if (a.name === lookfor_tag && a.startchar === tagstartchar && a.endchar === tagendchar)
                        return tag;
                }

            } else if (TwigTag && typeof (a.twigclass) !== "undefined" && a.twigclass !== "") {

                //Twig Tag
                if (a.twigclass + '.' + a.name === lookfor_tag && a.startchar === tagstartchar && a.endchar === tagendchar)
                    return tag;
            }
        }
    }

    //If nothing found then simplify the search

    for (s = 0; s < tagsets.length; s++) {
        let tagset = tagsets[s];
        let tags = getParamOptions(tagset, 'tag');

        for (let i = 0; i < tags.length; i++) {
            let tag = tags[i];
            let a = tag["@attributes"];

            if (lookfor_tag.indexOf(".") === -1) {

                //Conversion - OLD to Twig
                if (a.name === lookfor_tag && a.startchar === tagstartchar && a.endchar === tagendchar)
                    return tag;

            } else if (TwigTag && typeof (a.twigclass) !== "undefined" && a.twigclass !== "") {

                //Twig Tag
                if (a.twigclass + '.' + a.name === lookfor_tag && a.startchar === tagstartchar && a.endchar === tagendchar)
                    return tag;
            }
        }
    }

    return null;
}

function getParamEditForm(tagobject, line, positions, isnew, countparams, tagstartchar, postfix, tagendchar, paramvaluestring) {

    let result = renderParamBox(tagobject, "current_tagparameter", paramvaluestring);
    result += '<div class="dynamic_values"><span class="dynamic_values_label">Tag with parameter:</span> ';
    result += tagstartchar;
    result += temp_params_tag;
    result += postfix + '(<span id="current_tagparameter" style="">' + paramvaluestring + '</span>)';
    result += tagendchar + '</div>';
    result += '<div style="text-align:center;">';
    result += '<button id="clsave" onclick=\'return saveParams(event,' + countparams + ',' + line + ',' + positions[0] + ',' + positions[1] + ',' + isnew + ',"' + tagstartchar + '","' + tagendchar + '","' + postfix + '");\' class="btn btn-small button-apply btn-success">Save</button>';
    result += ' <button id="clclose" onclick=\'return closeModal(event);\' class="btn btn-small button-cancel btn-danger">Cancel</button>';
    result += '</div>';
    return result;
}

function saveParams(e, countParams, line_number, pos1, pos2, isNew, tagStartChar, tagEndchar, postfix) {

    updateParamString("fieldtype_param_", 1, countParams, "current_tagparameter", null, false);
    e.preventDefault();

    let tmp_params = document.getElementById('current_tagparameter').innerHTML;
    let result = tagStartChar + temp_params_tag + postfix;

    if (tmp_params !== "")
        result += '(' + tmp_params + ')';//{{ tag.edit(par1,par2) }} where ".edit" is the postfix

    result += tagEndchar;

    let cursor_from = {line: line_number, ch: pos1};
    let cursor_to = {line: line_number, ch: pos2};
    let editor = codemirror_editors[codemirror_active_index];
    let doc = editor.getDoc();
    doc.replaceRange(result, cursor_from, cursor_to, "");
    document.getElementById('layouteditor_Modal').style.display = "none";
    let cm = codemirror_editors[0];
    cm.focus();
    return false;
}

function closeModal(e) {
    e.preventDefault();

    document.getElementById('layouteditor_Modal').style.display = "none";
    const cm = codemirror_editors[0];
    cm.focus();
    return false;
}

function define_cmLayoutEditor() {
    define_cmLayoutEditor1('layouteditor', 'text/html');
}

//Used in layouteditor/php
function define_cmLayoutEditor1(modename, nextmodename) {
    CodeMirror.defineMode(modename, function (config, parserConfig) {
        const layouteditorOverlay =
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

        return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || nextmodename), layouteditorOverlay);
    });
}

function do_render_current_TagSets() {
    if (type_obj === null)
        return;

    layouttypeid = type_obj.value;

    let index = 0;
    let tabs = [];

    for (let i = 0; i < tagsets.length; i++) {
        let tagset = tagsets[i];
        let a = tagset["@attributes"];

        if (typeof (a.deprecated) == "undefined" || a.deprecated === "0") {

            tabs.push({
                'id': 'layouteditor_tags' + index + '_' + i + '', 'title': a.label,
                'content': '<p>' + a.description + '</p>' + renderTags(index, tagset)
            });
        }
    }

    if (tabs.length > 0)
        return renderTabs('layouteditor_fields', tabs);
    else
        return '<div class="FieldTagWizard"><p>No Tags available for this Layout Type</p></div>';
}

function renderTags(index, tagset) {

    let tagset_attributes = tagset["@attributes"];

    let tags = getParamOptions(tagset, 'tag');
    let result = '<div class="dynamic_values" style="padding-left:0 !important;">';

    for (let i = 0; i < tags.length; i++) {
        let tag_object = tags[i];
        let tag = tag_object["@attributes"];

        if (typeof (tag.deprecated) == "undefined" || tag.deprecated === "0") {
            let t = "";
            let params = getParamOptions(tag_object.params, 'param');

            let full_tagname = '';
            if (typeof (tag.twigclass) !== "undefined" && tag.twigclass !== "")
                full_tagname = tag.twigclass + '.' + tag.name;
            else
                full_tagname = tag.name;

            if (tagset_attributes.name === 'filters')
                full_tagname = tag.examplevalue + ' | ' + full_tagname;

            if (params.length === 0)
                t = '{{ ' + full_tagname + ' }}'; // t=tag.startchar+tag.name+tag.endchar;
            else
                t = '{{ ' + full_tagname + '(<span>Params</span>)' + ' }}'; //t=tag.startchar+full_tagname+':<span>Params</span>'+tag.endchar;

            result += '<div style="vertical-align:top;display:inline-block;">';

            if (typeof (tag.proversion) === "undefined" || parseInt(tag.proversion) !== 1) {
                result += '<a href=\'javascript:addTag("{{ "," }}","' + btoa(full_tagname) + '",' + params.length + ');\' class="btn-primary">' + t + '</a> ';
            } else {
                if (proversion) {
                    result += '<a href=\'javascript:addTag("{{ "," }}","' + btoa(full_tagname) + '",' + params.length + ');\' class="btn-primary">' + t + '</a> ';
                } else {
                    result += '<div style="display:inline-block;"><div class="btn-default">' + t + '</div></div> ';
                    result += '<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">Available in PRO Version</a></div>';
                }
            }
            result += tag.description;
            result += '</div>';
        }
    }

    result += '</div>';

    return result;
}

function addTabExtraEvents3() {
    //let layoutcode_textarea = document.getElementById('jform_layoutcode');
    //window.location.href = "#layoutcode-tab";
    //layoutcode-tab

    jQuery(function ($) {
        $(".nav-tabs a").click(function (e) {
            let a = e.target.href;

            let codepair = a.split("#");
            let code = codepair[1].replace('-tab', '');

            const index = textarea_findindex(code);

            if (index !== -1) {
                setTimeout(function () {
                    console.log(index);
                    codemirror_active_index = index;
                    codemirror_active_areatext_id = 'jform_' + code;
                    let cm = codemirror_editors[index];
                    cm.refresh();
                    /*
                    let h = window.innerHeight;
                    let rect = cm.getBoundingClientRect();
                    let editorHeight=h-rect.top-40;
                    cm.style.height = editorHeight+'px';
                    */
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
                let cm = codemirror_editors[index];
                cm.refresh();
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
    let cm = codemirror_editors[index];

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
    }, 100);
}

function addExtraEvent(index) {
    codemirror_active_index = index;
    let cm = codemirror_editors[index];
    cm.refresh();

    cm.on('dblclick', function () {
        let cr = cm.getCursor();
        let line = cm.getLine(cr.line);
        let positions = findTagInLine(cr.ch, line);

        if (positions != null) {
            let startchar = line.substring(positions[0], positions[0] + 1); //+1 to have 1 character
            if (startchar === '{') {
                let startchar2 = line.substring(positions[0] - 1, positions[0] + 1);
                if (startchar2 === '{{')
                    startchar = '{{';
            }

            let endchar = line.substring(positions[1] - 1, positions[1] - 1 + 1);//-1 because position ends after the tag
            if (endchar === '}') {
                let endchar2 = line.substring(positions[1] - 1, positions[1] - 1 + 2);
                if (endchar2 === '}}')
                    endchar = '}}';
            }

            let tag = line.substring(positions[0] + 1, positions[1] - 1);//-1 because position ends after the tag

            if (startchar === '{{') {
                positions[0] = positions[0] - 1;
                positions[1] = positions[1] + 1;
            }

            let postfix = ''; //todo
            let mousePos = cm.cursorCoords(cr, "window");
            showModalForm(startchar, postfix, endchar, tag, mousePos.top, mousePos.left, cr.line, positions, 0);
        }

    }, true);
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
        editor.style.height = editorHeight + 'px';
    }
}
