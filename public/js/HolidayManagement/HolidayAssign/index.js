(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
		
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

angular.module('hris', [])
        .controller('HolidayAssignController', function ($scope) {
            var $companyId = angular.element(document.getElementById('companyId'));
            var $branchId = angular.element(document.getElementById('branchId'));
            var $departmentId = angular.element(document.getElementById('departmentId'));
            var $designationId = angular.element(document.getElementById('designationId'));
            var $positionId = angular.element(document.getElementById('positionId'));
            var $serviceTypeId = angular.element(document.getElementById('serviceTypeId'));
            var $serviceEventTypeId = angular.element(document.getElementById('serviceEventTypeId'));
            var $employeeId = angular.element(document.getElementById('employeeId'));
            var $genderId = angular.element(document.getElementById('genderId'));
            var $employeeTypeId = angular.element(document.getElementById('employeeTypeId'));


            $scope.holidayList = document.holidayList;

            $scope.employeeList = [];
            $scope.alreadyAssignedEmpList = [];
            $scope.all = false;

            $scope.checkAll = function (item) {
                for (var i = 0; i < $scope.employeeList.length; i++) {
                    $scope.employeeList[i].checked = item;
                }
            };


            $scope.holidayChangeFn = function () {
                if ($scope.holiday == null) {
                    $scope.alreadyAssignedEmpList = [];
                    var empList = angular.copy($scope.employeeList);
                    $scope.employeeList = [];
                    for (var i in empList) {
                        var emp = empList[i];
                        emp.checked = ($scope.alreadyAssignedEmpList.indexOf(emp.EMPLOYEE_ID) >= 0);
                        $scope.employeeList.push(emp);
                    }
                    return;
                }

                $scope.all = false;

                window.app.serverRequest(document.wsGetHolidayAssignedEmployees, {
                    holidayId: $scope.holiday
                }).then(function (response) {
                    if (response.success) {
                        $scope.$apply(function () {
                            $scope.alreadyAssignedEmpList = response.data;
                            var empList = angular.copy($scope.employeeList);
                            $scope.employeeList = [];
                            for (var i in empList) {
                                var emp = empList[i];
                                emp.checked = ($scope.alreadyAssignedEmpList.indexOf(emp.EMPLOYEE_ID) >= 0);
                                $scope.employeeList.push(emp);
                            }
                        });
                    } else {
                        console.log("getHolidayAssignedEmployees=>", response.error);
                    }

                }, function (failure) {
                });
            };

            $scope.view = function () {
                window.app.pullDataById(document.wsGetEmployeeList, {
                    companyId: $companyId.val(),
                    branchId: $branchId.val(),
                    departmentId: $departmentId.val(),
                    designationId: $designationId.val(),
                    positionId: $positionId.val(),
                    serviceTypeId: $serviceTypeId.val(),
                    serviceEventTypeId: $serviceEventTypeId.val(),
                    employeeId: $employeeId.val(),
                    genderId: $genderId.val(),
                    employeeTypeId: $employeeTypeId.val()
                }).then(function (response) {
                    $scope.$apply(function () {
                        $scope.employeeList = [];
                        var empList = response.data;
                        for (var i in empList) {
                            var emp = empList[i];
                            emp.checked = ($scope.alreadyAssignedEmpList.indexOf(emp.EMPLOYEE_ID) >= 0);
                            $scope.employeeList.push(emp);
                        }
                    });
                    window.app.scrollTo('employeeTable');

                }, function (failure) {

                });
                $scope.holidayChangeFn();
            };

            $scope.assign = function () {
                if ($scope.employeeList.length == 0) {
                    window.app.showMessage("No Employees to Assign.", "error");
                    return;
                }
                if ($scope.holiday == null) {
                    window.app.showMessage("Select the holiday first to assign to", "error");
                    return;
                }


                var checkedEmpList = [];
                for (var index in $scope.employeeList) {
                    var tmpStatus='D';
                    var tempData=[];
                    if ($scope.employeeList[index].checked) {
                    tmpStatus='A';
                    }
                    tempData.push({
                        id: $scope.employeeList[index].EMPLOYEE_ID, 
                        s:  tmpStatus
                        });
                        checkedEmpList.push(tempData);
                }
                console.log(checkedEmpList);
                var reattendance = function (employeeList, fromDate, toDate) {
                    var employeeIdList = [];
                    $.each(employeeList, function (key, employeeId) {
                        var employeeData = {
                            EMPLOYEE_ID: employeeId,
                            FROM_DATE: fromDate,
                            TO_DATE: toDate
                        }
                        employeeIdList.push(employeeData);
                    });
                    window.app.bulkServerRequest(document.regenAttendanceLink, employeeIdList, function () {
                        window.app.showMessage("Holiday Assigned Successfully");
                    }, function (data, error) {
                        app.showMessage(error, 'error');
                    });
                };

                window.app.serverRequest(document.wsAssignHolidayToEmployees, {
                    holidayId: $scope.holiday,
                    employeeIdList: JSON.stringify(checkedEmpList)
                }).then(function (response) {
                    if (response.success) {
                        var holiday = $scope.holidayList.filter(function (item) {
                            return item['HOLIDAY_ID'] == $scope.holiday;
                        })[0];
                        var employeeIdList = [];
                        for (var index in $scope.employeeList) {
                            employeeIdList.push($scope.employeeList[index].EMPLOYEE_ID);
                        }
                        reattendance(employeeIdList, holiday['START_DATE'], holiday['END_DATE']);
                    } else {
                        window.app.showMessage(response.error);
                    }
                }, function (failure) {
                    console.log("shift Assign Filter Success Response", failure);
                });

            };

        });