(function ($, app) {
    "use strict";
    $(document).ready(function () {
    	let $employeeIdBased = $("#employeeIdBased");
    	let $employeeCodeBased = $("#employeeCodeBased");
    	let $fiscalYearId = $("#fiscalYear");
    	let $table = $("#table");
    	var excelData;
      let basedOnFlag = 2;
    	$("select").select2();
      $("#employeeIdBased").click(function(){ basedOnFlag = 1; });
      $("#employeeCodeBased").click(function(){ basedOnFlag = 2; });
    	var columns = [];
    	columns.push({field: "A", title: "Sr. NO", width: 80});
    	columns.push({field: "B", title: "Code", width: 120});
    	columns.push({field: "C", title: "Employee Name", width: 120});
      columns.push({field: "D", title: "Position", width: 120});
      columns.push({field: "E", title: "Opening Grade", width: 120});
      columns.push({field: "F", title: "Additional Grade", width: 120});
      columns.push({field: "G", title: "Grade Value", width: 120});
      columns.push({field: "H", title: "Grade Date", template: "#:excelDateToJSDate(H)#", width: 120});
      columns.push({field: "I", title: "Miti", width: 120});
      columns.push({field: "J", title: "Remarks", width: 120});
    	app.initializeKendoGrid($table, columns);

    $("#submit").on('click', function(){
	// if(prompt("Make sure all options are correctly selected. Type CONFIRM to proceed.") !== "CONFIRM"){ return; }
            if(!confirm("confirm?")){ return; }
              excelData = JSON.stringify(excelData);
              let data = excelData;
              excelData = JSON.parse(excelData);
              data = JSON.parse(data);
              for(let x in data){
                delete data[x]['A'];
                delete data[x]['C'];
                delete data[x]['D'];
                delete data[x]['I'];
                data[x]['H'] = excelDateToJSDate(data[x]['H']);
            }
            var fileUploadedFlag = document.getElementById("excelImport").files.length;
            var fiscalYearId = $fiscalYearId.val();
            if(fileUploadedFlag == 0 || fiscalYearId == -1){
                app.showMessage('One or more input missing', 'warning');
                return;
            }
            
        	app.serverRequest(document.updateGradesLink, {data : data, fiscalYearId: $fiscalYearId.val(), basedOn: basedOnFlag}).then(function(){
                app.showMessage('Operation successfull', 'success');
            }, function (error) {
                console.log(error);
            });
    	});

      	$("#excelImport").change(function(evt){
            var selectedFile = evt.target.files[0];
            var reader = new FileReader();
            reader.onload = function(event) {
              var data = event.target.result;
              var workbook = XLSX.read(data, {
                  type: 'binary'
              });
              workbook.SheetNames.forEach(function(sheetName) {
              //var XL_row_object = XLSX.utils.sheet_to_row_object_array(workbook.Sheets[sheetName]);
    				  var XL_row_object = XLSX.utils.sheet_to_json(workbook.Sheets.Sheet1, {header: "A"}); 
    				  var json_object = JSON.stringify(XL_row_object);
                  excelData = JSON.parse(json_object);
                  app.renderKendoGrid($table, excelData);
              });
            }
            reader.onerror = function(event) {
              console.error("File could not be read! Code " + event.target.error.code);
            };
            reader.readAsBinaryString(selectedFile);
      	});
    });
})(window.jQuery, window.app);