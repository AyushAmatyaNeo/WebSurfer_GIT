(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.startEndDatePickerWithNepali('nepaliStartDate1', 'startDate', 'nepaliEndDate1', 'endDate');
		
        let $branch = $('#branchId');
        let $province= $('#province');
        let populateBranch ;

        $province.on("change", function () {
            populateBranch = [];
            $.each(document.braProv, function(k,v){
                if(v == $province.val()){
                    populateBranch.push(k);
                }
            });
            $branch.val(populateBranch).change();
        });
    });
})(window.jQuery, window.app);




