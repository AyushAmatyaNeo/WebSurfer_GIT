(function ($) {
    'use strict';
    $(document).ready(function () {
        var $table=$('#levelTable');
        var editAction = document.acl.ALLOW_UPDATE =='Y' ? '<a class="btn-edit" title="Edit" href="'+document.editLink +'/#:LEVEL_ID#" style="height:17px;"> <i class="fa fa-edit"></i> </a>':'';
        var deleteAction=document.acl.ALLOW_DELETE =='Y' ? '<a class="confirmation btn-delete" title="Delete" href="'+document.deleteLink+'/#:LEVEL_ID#" style="height:17px;"><i class="fa fa-trash-o"></i> </a>':'';
        var action=editAction+deleteAction;

        app.initializeKendoGrid($table,[
            {field:"LEVEL_CODE", title:"Level Code",width:100},
            {field:"LEVEL_NAME",title:"Level Name",width:100},
            {field:"AMOUNT",title:"Amount",width:100},
            {field:"REMARKS",title:"Remarks",width:100},
            {field:"LEVEL_ID",title:"Action",width:100,template:action}
        ],null,null,null,'Level List');

        app.searchTable('levelTable',['LEVEL_CODE','LEVEL_NAME']);

        $('#excelExport').on('click',function(){
            app.excelExport($table,{
                'LEVEL_CODE':'Level Code',
                'LEVEL_NAME':'Level Name',
                'AMOUNT':'Amount',
                'REMARKS':'Remarks'
            },'Level list');
        });

        $('#pdfExport').on('click',function(){
            app.exportToPDF($table,{
                'LEVEL_CODE':'Level Code',
                'LEVEL_NAME':'Level Name',
                'AMOUNT':'Amount',
                'REMARKS':'Remarks'
            },'Level list');
        });

        app.pullDataById("",{}).then(function (response){
            app.renderKendoGrid($table, response.data);
        }, function (error){

        });
});
})(window.jQuery);




