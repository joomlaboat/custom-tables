/* Example definition of a simple mode that understands a subset of
 * JavaScript:
 */

(function (mod) {
    if (typeof exports == "object" && typeof module == "object") // CommonJS
        mod(require("../../lib/codemirror"), require("../../addon/mode/simple"));
    else if (typeof define == "function" && define.amd) // AMD
        define(["../../lib/codemirror", "../../addon/mode/simple"], mod);
    else // Plain browser env
        mod(CodeMirror);
})(function (CodeMirror) {
    "use strict";

    /*
    CodeMirror.defineSimpleMode("layouteditor", {

        // The start state contains the rules that are intially used
        start: [

            {regex: /\[.[a-z]*:[a-z]*\]/, token: "layouteditortagwithparams"},

            {regex: /\[.[a-z]*\]/, token: "layouteditortag"},


        ],

        // The meta property contains global information about the mode. It
        // can contain properties like lineComment, which are supported by
        // all modes, and also directives like dontIndentStates, which are
        // specific to simple modes.

        meta: {
            dontIndentStates: ["comment"],
            lineComment: "//"
        }

    });
    */
//(:[a-z]*)
//{regex: /:[a-z]*\]/, token: "layouteditortagparams"},
//{regex: /\[.[a-z]*:[a-z]*\]/, token: "layouteditortagwithparams"},
    CodeMirror.defineMIME("text/html", "layouteditor");
});


//https://www.regextester.com/ - helps with regex
