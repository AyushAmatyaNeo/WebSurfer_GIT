
function excelDateToJSDate(serial) {
      var utc_days  = Math.floor(serial - 25569);
      var utc_value = utc_days * 86400;      
      var date_info = new Date(utc_value * 1000);
      var monthShortNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
      return date_info.getDate()+"-"+monthShortNames[date_info.getMonth()]+"-"+date_info.getFullYear();
}
