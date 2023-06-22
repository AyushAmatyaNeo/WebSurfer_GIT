(function ($) {
    'use strict';
    $(document).ready(function () {

        var $serviceEventTypeTable = $('#serviceEventTypeTable');
        var editAction = document.acl.ALLOW_UPDATE == 'Y' ? '<a class="btn-edit" title="Edit" href="' + document.editLink + '/#:SERVICE_EVENT_TYPE_ID#" style="height:17px;"> <i class="fa fa-edit"></i></a>' : '';
        var deleteAction = document.acl.ALLOW_DELETE == 'Y' ? '<a class="confirmation btn-delete" title="Delete" href="' + document.deleteLink + '/#:SERVICE_EVENT_TYPE_ID#" style="height:17px;"><i class="fa fa-trash-o"></i></a>' : '';
        var action = editAction + deleteAction;

        app.initializeKendoGrid($serviceEventTypeTable, [
            {field: "SERVICE_EVENT_TYPE_CODE", title: "Service Event Type Code", template: "<span>#: (SERVICE_EVENT_TYPE_CODE == null) ? '-' : SERVICE_EVENT_TYPE_CODE #</span>", width: 120},
            {field: "SERVICE_EVENT_TYPE_NAME", title: "Service Event Type", template: "<span>#: (SERVICE_EVENT_TYPE_NAME == null) ? '-' : SERVICE_EVENT_TYPE_NAME #</span>", width: 120},
            {field: "SERVICE_EVENT_TYPE_ID", title: "Action", width: 120, template: action}

        ], null, null, null, 'Service Event Types');
        
        app.searchTable('serviceEventTypeTable',['SERVICE_EVENT_TYPE_CODE','SERVICE_EVENT_TYPE_NAME']);
        
        app.pdfExport(
                'serviceEventTypeTable',
                {
                    'SERVICE_EVENT_TYPE_NAME': 'Service Event Type'
                }
        );

        app.pullDataById("", {}).then(function (response) {
            app.renderKendoGrid($serviceEventTypeTable, response.data);
        }, function (error) {

        });

        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Service Event Type List');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Service Event Type List');
        });

    });
})(window.jQuery, window.app);