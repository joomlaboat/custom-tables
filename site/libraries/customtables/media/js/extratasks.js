/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage extratasks.js
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
let extraTasksUpdate_count = 0;
let extraTasksUpdate_startindex = 0;
let extraTasksUpdate_stepsize = 10;

function extraTasksUpdate(task, old_params, new_params, tableid, fieldid, tabletitle, fieldtitle) {
    let result = '';

    switch (task) {
        case 'updateimages':
            result = '<h3>Processing image files...</h3>';
            break;

        case 'updatefiles':
            result = '<h3>Processing files...</h3>';
            break;

        case 'updateimagegallery':
            result = '<h3>Processing image files...</h3>';
            break;

        case 'updatefilebox':
            result = '<h3>Processing files...</h3>';
            break;

        default:
            return false;
            break;
    }

    //Delete non ASCII characters, just in case.
    let op = Base64.decode(old_params).replace(/[^ -~]+/g, "");
    let np = Base64.decode(new_params).replace(/[^ -~]+/g, "");

    result += '<p><b>Table:</b> ' + tabletitle + '<br/><b>Field:</b> ' + fieldtitle + '</p>';
    result += '<table><tbody><tr><td><b>Old Parameters:</b></td><td>' + op + '</td></tr><tr><td><b>New Parameters:</b></td><td>' + np + '</td></tr></tbody></table>';
    result += '<div id="ctStatus"></div><br/>';

    result += '<div class="progress progress-striped active"><div id="ct_progressbar" class="ctProgressBar" role="progressbar" style="width: 0%;"></div></div><br/><p>Please keep this window open.</p>';

    ctShowPopUp(result, false);
    ctQueryAPI(task, old_params, new_params, tableid, fieldid);
}

function ctQueryAPI(task, old_params, new_params, tableid, fieldid) {
    let parts = location.href.split("/administrator/");
    let websiteroot = parts[0] + "/administrator/";
    let url = websiteroot + "index.php?option=com_customtables&view=api&frmt=json&task=" + task + "&old_typeparams=" + old_params;
    url += "&new_typeparams=" + new_params + "&fieldid=" + fieldid + "&startindex=" + extraTasksUpdate_startindex + "&stepsize=" + extraTasksUpdate_stepsize;

    if (typeof fetch === "function") {
        fetch(url, {method: 'GET', mode: 'no-cors', credentials: 'same-origin'}).then(function (response) {
            if (response.ok) {
                response.json().then(function (json) {
                    if (json.success == 1) {

                        if (extraTasksUpdate_count == 0) {
                            extraTasksUpdate_count = json.count;
                            if (extraTasksUpdate_count == 0) {
                                document.getElementById("ctStatus").innerHTML = "Task is complete.";
                                setTimeout(function () {
                                    ctHidePopUp();
                                    location.href = 'index.php?option=com_customtables&view=listoffields&tableid=' + tableid;
                                }, 500);
                                return;
                            }
                        }

                        if (json.stepsize < extraTasksUpdate_stepsize)
                            extraTasksUpdate_stepsize = json.stepsize;

                        document.getElementById("ctStatus").innerHTML = "File" + (extraTasksUpdate_count == 1 ? "" : "s") + ": " + (extraTasksUpdate_startindex + extraTasksUpdate_stepsize) + " of " + extraTasksUpdate_count;

                        let bar = document.getElementById("ct_progressbar");
                        let p = Math.floor(100 * (extraTasksUpdate_startindex + extraTasksUpdate_stepsize) / extraTasksUpdate_count);

                        bar.style = "width: " + p + "%;";

                        if (extraTasksUpdate_startindex == extraTasksUpdate_count) {
                            setTimeout(function () {

                                document.getElementById("ctStatus").innerHTML = "Completed.";
                                location.href = 'index.php?option=com_customtables&view=listoffields&tableid=' + tableid;
                                ctHidePopUp();

                            }, 500);

                            return;
                        }

                        extraTasksUpdate_startindex += extraTasksUpdate_stepsize;

                        if (extraTasksUpdate_startindex + extraTasksUpdate_stepsize > extraTasksUpdate_count)
                            extraTasksUpdate_stepsize = extraTasksUpdate_count - extraTasksUpdate_startindex;

                        if (extraTasksUpdate_stepsize == 0) {
                            setTimeout(function () {

                                document.getElementById("ctStatus").innerHTML = "Completed.";
                                location.href = 'index.php?option=com_customtables&view=listoffields&tableid=' + tableid;
                                ctHidePopUp();

                            }, 500);

                            return;
                        }

                        setTimeout(function () {
                            ctQueryAPI(task, old_params, new_params, tableid, fieldid);
                        }, 500);
                    } else {
                        document.getElementById("ctStatus").innerHTML = "ERROR: " + JSON.stringify(json);
                    }
                });
            } else {
                console.log('Network request for products.json failed with response ' + response.status + ': ' + response.statusText);
            }
        }).catch(function (err) {
            console.log('Fetch Error :', err);
        });
    }
}
