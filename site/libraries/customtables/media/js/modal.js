/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

function ctResizeModalBox() {
    setTimeout(
        function () {

            let modal = document.getElementById('ctModal_box');

            let h = window.innerHeight;
            let rect = modal.getBoundingClientRect();

            //let content_height;
            let modalBoxHeightChanged = false;
            if (rect.bottom > h - 100) {
                let content_height = h - 150;
                modal.style.top = "50px";
                modal.style.height = content_height + "px";

                modalBoxHeightChanged = true;
            }
            //else
            //content_height=rect.bottom-rect.top;

            if (modalBoxHeightChanged) {

            }

            let box = document.getElementById("ctModal_box");
            box.style.visibility = "visible";

        }, 100);

    return true;
}

function ctShowModal(showCloseButton) {
    // Get the modal

    let modal = document.getElementById('ctModal');

    if (showCloseButton) {
        // Get the <span> element that closes the modal
        let span = document.getElementsByClassName("ctModal_close")[0];

        // When the user clicks on <span> (x), close the modal
        span.onclick = function () {
            modal.style.display = "none";
            //let cm=codemirror_editors[0];
            //cm.focus();
        };

        // When the user clicks anywhere outside the modal, close it
        window.onclick = function (event) {
            if (event.target === modal) {
                modal.style.display = "none";
                //let cm=codemirror_editors[0];
                //cm.focus();
            }
        };
    }

    let box = document.getElementById("ctModal_box");
    box.style.visibility = "hidden";
    box.style.height = "auto";

    modal.style.display = "block";
    let e = document.documentElement;

    let doc_w = e.clientWidth;
    let doc_h = e.clientHeight;

    let w = box.offsetWidth;
    let h = box.offsetHeight;

    //let x=left-w/2;
    let x = (doc_w / 2) - w / 2;
    if (x < 10)
        x = 10;

    if (x + w + 10 > doc_w)
        x = doc_w - w - 10;

    //let y=top-h/2;
    let y = (doc_h / 2) - h / 2;


    if (y < 50)
        y = 50;

    if (y + h + 50 > doc_h) {
        y = doc_h - h - 50;
    }

    box.style.left = x + 'px';
    box.style.top = y + 'px';

    ctResizeModalBox();
}

function ctShowPopUp(content_html, showCloseButton) {
    let ctModal_close = document.getElementById("ctModal_close");

    if (showCloseButton)
        ctModal_close.style.display = "block";
    else
        ctModal_close.style.display = "none";

    let ctModal_content = document.getElementById("ctModal_content");
    ctModal_content.innerHTML = content_html;

    insertAndExecuteScripts("ctModal_content");

    setTimeout(function () {
        insertAndExecute("ctModal_content");
    }, 300);

    ctShowModal(showCloseButton);
}

function insertAndExecuteScripts(id) {
    const scripts = Array.prototype.slice.call(document.getElementById(id).getElementsByTagName("script"));
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src !== "") {
            const tag = document.createElement("script");
            tag.src = scripts[i].src;
            document.getElementsByTagName("head")[0].appendChild(tag);
        }
    }
}

function insertAndExecute(id) {
    const scripts = Array.prototype.slice.call(document.getElementById(id).getElementsByTagName("script"));
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src === "") {
            eval(scripts[i].innerHTML);
        }
    }
}


function ctHidePopUp() {
    // Get the modal

    let modal = document.getElementById('ctModal');
    modal.style.display = "none";
}



