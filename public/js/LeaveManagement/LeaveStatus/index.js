(function ($, app) {
    'use strict';
    $(document).ready(function () {
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $tableContainer = $("#leaveRequestStatusTable");
        var $leave = $('#leaveId');
        var $status = $('#leaveRequestStatusId');
        var $search = $('#search');
        var $bulkActionDiv = $('#bulkActionDiv');
        var $bulkBtns = $(".btnApproveReject");
        var $superpower = $("#super_power");
        var $leaveYear = $('#leaveYear');

        $.each(document.searchManager.getIds(), function (key, value) {
            $('#' + value).select2();
        });

        $leave.select2();
        $status.select2();
        $leaveYear.select2();

        var columns = [ 
            {field: "EMPLOYEE_CODE", title: "Code"},
            {field: "FULL_NAME", title: "Employee"},
            {field: "LEAVE_ENAME", title: "Leave"},
            {title: "Requested Date",
                columns: [{
                        field: "APPLIED_DATE_AD",
                        title: "AD",
                        template: "<span>#: (APPLIED_DATE_AD == null) ? '-' : APPLIED_DATE_AD #</span>"},
                    {field: "APPLIED_DATE_BS",
                        title: "BS",
                        template: "<span>#: (APPLIED_DATE_BS == null) ? '-' : APPLIED_DATE_BS #</span>"}]},
            {title: "From Date",
                columns: [{
                        field: "START_DATE_AD",
                        title: "AD",
                        template: "<span>#: (START_DATE_AD == null) ? '-' : START_DATE_AD #</span>"},
                    {field: "START_DATE_BS",
                        title: "BS",
                        template: "<span>#: (START_DATE_BS == null) ? '-' : START_DATE_BS #</span>"}]},
            {title: "To Date",
                columns: [{
                        field: "END_DATE_AD",
                        title: "AD",
                        template: "<span>#: (END_DATE_AD == null) ? '-' : END_DATE_AD #</span>"},
                    {field: "END_DATE_BS",
                        title: "BS",
                        template: "<span>#: (END_DATE_BS == null) ? '-' : END_DATE_BS #</span>"}]},
            {field: "NO_OF_DAYS", title: "Duration"},
            {field: "HALF_DAY_DETAIL", title: "Type"},
            {field: "STATUS", title: "Status"},
            {field: "ID", title: "Action", template: `
            <span class="clearfix">                             
                <a class="btn  btn-icon-only btn-success" href="${document.viewLink}/#: ID #" style="height:17px; width:13px" title="view">
                <i class="fa fa-search-plus"></i>
                </a> 
                #if(SHOW_LEAVE_FORM =='Y'){#
                <a class="btn  btn-icon-only btn-success" href="${document.lfcLink}/#: ID #" style="height:17px; width:13px" title="view">
                <i class="fa fa-search"></i>
                </a>
                #}# 
            </span>`}
        ];
        columns = app.prependPrefColumns(columns);
        var pk = 'ID';
        var grid = app.initializeKendoGrid($tableContainer, columns, null, {id: pk, atLast: false, fn: function (selected) {
                if (selected) {
                    $bulkActionDiv.show();
                } else {
                    $bulkActionDiv.hide();
                }
            }}, null, 'Leave Status Report.xlsx');
        app.searchTable($tableContainer, ["FULL_NAME", "EMPLOYEE_CODE"]);
  
        var map = {
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': 'Name',
            'FUNCTIONAL_TYPE_EDESC': 'Functional Type',
			'POSITION_NAME' : 'Position',
            'LEAVE_ENAME': 'Leave',
            'APPLIED_DATE_AD': 'Applied Date(AD)',
            'APPLIED_DATE_BS': 'Applied Date(BS)',
            'START_DATE_AD': 'Start Date(AD)',
            'START_DATE_BS': 'Start Date(BS)',
            'END_DATE_AD': 'End Date(AD)',
            'END_DATE_BS': 'End Date(BS)',
            'NO_OF_DAYS': 'No Of Days',
            'STATUS': 'Status',
            'REMARKS': 'Remarks',
			'SUB_EMPLOYEE_NAME': 'Substitute Employee',
            'RECOMMENDER_NAME': 'Recommender',
            'APPROVER_NAME': 'Approver',
            'RECOMMENDED_BY_NAME': 'Recommended By',
            'APPROVED_BY_NAME': 'Approved By',
            'RECOMMENDED_REMARKS': 'Recommended Remarks',
            'APPROVED_REMARKS': 'Approved Remarks',
            'RECOMMENDED_DT': 'Approved Date',
            'APPROVED_DT': 'Approved Date'
        };
        map = app.prependPrefExportMap(map);

        $search.on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['leaveId'] = $leave.val();
            q['leaveRequestStatusId'] = $status.val();
            q['fromDate'] = $('#fromDate').val();
            q['toDate'] = $('#toDate').val();
            q['recomApproveId'] = $('#recomApproveId').val();
            q['leaveYear'] = $leaveYear.val();
            App.blockUI({target: "#hris-page-content"});
            app.pullDataById(document.pullLeaveRequestStatusListLink, q).then(function (success) {
                App.unblockUI("#hris-page-content");
                app.renderKendoGrid($tableContainer, success.data);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
            });
        });

        $('#excelExport').on('click', function () {
            app.excelExport($tableContainer, map, "Leave Request List.xlsx");
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($tableContainer, map, "Leave Request List.pdf", 'A2');
        });

        $bulkBtns.bind("click", function () {
            var list = grid.getSelected();
            var action = $(this).attr('action');
            var superPower = $superpower.prop('checked');

            var selectedValues = [];
            for (var i in list) {
                selectedValues.push({id: list[i][pk], action: action, status: list[i]['STATUS'], super_power: superPower});
            }
            app.bulkServerRequest(document.bulkLink, selectedValues, function () {
                $search.trigger('click');
            }, function (data, error) {

            });
        });
        
        
        $leaveYear.on('change', function () {
            let selectedLeaveYear = $(this).val();
            let leaveList = document.allLeaveForReport[selectedLeaveYear];
            app.populateSelect($leave, leaveList, 'LEAVE_ID', 'LEAVE_ENAME', 'All Leaves', -1, -1);
        });
        

    });
})(window.jQuery, window.app);
