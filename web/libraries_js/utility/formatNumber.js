/************************************************************************
*	formatNumber.js
*
*  Functions for formatting numbers.
*
*  Original script by Duncan Crombie 
*  http://web.archive.org/web/20050722074257/members.ozemail.com.au/~dcrombie/format.html
* 
************************************************************************/

// Define some constants for use later
var separator = ",";	// use comma as 000's separator
var decpoint = ".";	// use period as decimal point
var percent = "%";	// use percent symbol as percent sign
var currency = "$";	// use dollar sign for currency


// The main function for formatting a number
// Arguments:
// 	number: the number to format
// 	format: a string containg the mask used to format the number
//					# = show digit if required
//					0 = always show digit
//					, = use thousands separator
//					% = display as a percentage
//					$ = display as a dollar amount
// Example: formatNumber(1234.559, "$,.##") = "$1,234.56"
function formatNumber(number, format, print) {  // use: formatNumber(number, "format")
    if (print) document.write("formatNumber(" + number + ", \"" + format + "\")<br>");
    
    if (number - 0 != number) {
        // alert('number is not a number!');
        return 0;  // if number is NaN return null
    }
    var useSeparator = format.indexOf(separator) != -1;  // use separators in number
    var usePercent = format.indexOf(percent) != -1;  // convert output to percentage
    var useCurrency = format.indexOf(currency) != -1;  // use currency format
    var isNegative = (number < 0);
    number = Math.abs (number);
    if (usePercent) number *= 100;
    format = strip(format, separator + percent + currency);  // remove key characters
    number = "" + number;  // convert number input to string
    
    // split input value into LHS and RHS using decpoint as divider
    var dec = number.indexOf(decpoint) != -1;
    var nleftEnd = (dec) ? number.substring(0, number.indexOf(".")) : number;
    var nrightEnd = (dec) ? number.substring(number.indexOf(".") + 1) : "";
    
    // split format string into LHS and RHS using decpoint as divider
    dec = format.indexOf(decpoint) != -1;
    var sleftEnd = (dec) ? format.substring(0, format.indexOf(".")) : format;
    var srightEnd = (dec) ? format.substring(format.indexOf(".") + 1) : "";
    
    // adjust decimal places by cropping or adding zeros to LHS of number
    if (srightEnd.length < nrightEnd.length) {
        var nextChar = nrightEnd.charAt(srightEnd.length) - 0;
        nrightEnd = nrightEnd.substring(0, srightEnd.length);
        if (nextChar >= 5) nrightEnd = "" + ((nrightEnd - 0) + 1);  // round up
        
        // patch provided by Patti Marcoux 1999/08/06
        while (srightEnd.length > nrightEnd.length) {
            nrightEnd = "0" + nrightEnd;
        }
        
        if (srightEnd.length < nrightEnd.length) {
            nrightEnd = nrightEnd.substring(1);
            nleftEnd = (nleftEnd - 0) + 1;
        }
    } else {
        for (var i=nrightEnd.length; srightEnd.length > nrightEnd.length; i++) {
            if (srightEnd.charAt(i) == "0") nrightEnd += "0";  // append zero to RHS of number
            else break;
        }
    }

    // adjust leading zeros
    sleftEnd = strip(sleftEnd, "##");  // remove hashes from LHS of format
    while (sleftEnd.length > nleftEnd.length) {
        nleftEnd = "0" + nleftEnd;  // prepend zero to LHS of number
    }

    if (useSeparator) nleftEnd = separate(nleftEnd, separator);  // add separator
    var output = nleftEnd + ((nrightEnd != "") ? "." + nrightEnd : "");  // combine parts
    output = ((useCurrency) ? currency : "") + output + ((usePercent) ? percent : "");
    if (isNegative) {
        // patch suggested by Tom Denn 25/4/2001
        output = (useCurrency) ? "(" + output + ")" : "-" + output;
    }

    return output;
}

// Strip all characters in 'chars' from input
function strip(input, chars) {
    var output = "";  // initialise output string
    for (var i=0; i < input.length; i++)
    if (chars.indexOf(input.charAt(i)) == -1)
        output += input.charAt(i);
    return output;
}

// Format input using 'separator' to mark 000's
function separate(input, separator) {  
    input = "" + input;
    var output = "";  // initialise output string
    for (var i=0; i < input.length; i++) {
        if (i != 0 && (input.length - i) % 3 == 0) output += separator;
        output += input.charAt(i);
    }
    return output;
}
