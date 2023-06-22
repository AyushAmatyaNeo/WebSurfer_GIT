(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.startEndDatePickerWithNepali('nepaliStartDate1', 'fromDate', 'nepaliEndDate1', 'toDate', function (fromDate, toDate) {
            if (fromDate <= toDate) {
                var oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
                var diffDays = Math.abs((fromDate.getTime() - toDate.getTime()) / (oneDay));
                var newValue = diffDays + 1;
                $("#duration").val(newValue);
            }
        });
        var $holidayId = $('#holidayId');
        var $fromDate = $("#fromDate");
        var $toDate = $("#toDate");

        app.populateSelect($holidayId, document.holidayList, 'HOLIDAY_ID', 'HOLIDAY_ENAME', "Select a holiday", null);
        var holidayChange = function ($this) {
            var value = $this.val();
            if (value == null || value == "" || value == -1) {
                return;
            }

            var startDate = app.getSystemDate(document.holidayList[value]["START_DATE"]);
            var endDate = app.getSystemDate(document.holidayList[value]["END_DATE"]);

            $fromDate.datepicker('setStartDate', startDate);
            $fromDate.datepicker('setEndDate', endDate);

            $toDate.datepicker('setStartDate', startDate);
            $toDate.datepicker('setEndDate', endDate);

            $fromDate.datepicker('setDate', startDate);
            $toDate.datepicker('setDate', endDate);

        };

        $holidayId.on('change', function () {
            holidayChange($(this));
        });

        var employeeId = $('#employeeId').val();
        window.app.floatingProfile.setDataFromRemote(employeeId);

        holidayChange($holidayId);
        app.setLoadingOnSubmit("workOnHoliday-form");
    });
})(window.jQuery, window.app);

