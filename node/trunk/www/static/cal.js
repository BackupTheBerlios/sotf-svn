var tags_before_clock = "<span class=date2> // " 

var tags_after_clock  = "// </span>"

var tags_before_date = "<span class=date>&nbsp;&nbsp;"

var tags_after_date = "</span> "



if(navigator.appName == "Netscape") {

document.write('<div id="clock"></div>');

}



if (navigator.appVersion.indexOf("MSIE") != -1){

document.write('<span id="clock"></span>');

}



var element = null;

  

function upclock(){ 

  var dte = new Date();

  var hrs = dte.getHours();

  var min = dte.getMinutes(); 

  var sec = dte.getSeconds();

  var year = dte.getYear();

  var day = dte.getDate() ;

  var month = dte.getMonth() + 1;

  var col = ":";

  var dot = ".";

  var spc = " ";



  year = year - 2000;

  if(year<0)year = year + 1900;

  

  if (min<=9) min="0"+min;

  if (sec<=9) sec="0"+sec;

  if (hrs<=9) hrs="0"+hrs;

  if (day<=9) day="0"+day;

  if (month<=9) month="0"+month;

  if (year<=9) year="0"+year;

 

  if(navigator.appName == "Netscape"){

    if(document.getElementById){

      element = document.getElementById("clock");

    }else if(document.all){

      element = document.all["clock"];

    }else if(document.layers){

      element = document.layers["clock"];

    }

    element.innerHTML = (tags_before_date+day+dot+month+dot+year+tags_after_date+tags_before_clock+hrs+col+min+col+sec+spc+tags_after_clock);

  }else{

  	clock.innerHTML = (tags_before_date+day+dot+month+dot+year+tags_after_date+tags_before_clock+hrs+col+min+col+sec+spc+tags_after_clock);

  }

}