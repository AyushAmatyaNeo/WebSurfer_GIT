
(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();

        var inputFieldId = "form-levelName";
        var formId = "level-form";
        var tableName = "HRIS_LEVELS";
        var columnName = "LEVEL_NAME";
        var checkColumnName = "LEVEL_ID";
        var selfId = $("#levelId").val();
        if (typeof (selfId) == "undefined") {
            selfId = 0;
        }
        
        app.checkUniqueConstraints("levelId",formId,tableName,"LEVEL_ID",checkColumnName,selfId);
        app.checkUniqueConstraints(inputFieldId, formId, tableName, columnName, checkColumnName, selfId, function () {
            App.blockUI({target: "#hris-page-content"});
        });
    });
})(window.jQuery, window.app);
