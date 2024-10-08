//This file contains all functions used in the app.

function __calculate_amount(calculation_type, calculation_amount, amount){

	var calculation_amount = parseFloat(calculation_amount);
	calculation_amount = isNaN(calculation_amount) ? 0 : calculation_amount;

	var amount = parseFloat(amount);
	amount = isNaN(amount) ? 0 : amount;	
	
	switch(calculation_type){
		case 'fixed':
			return parseFloat(calculation_amount);
		case 'percentage':
			return parseFloat((calculation_amount/100)*amount);
		default:
			return 0;
	}
}

//Add specified percentage to the input amount.
function __add_percent(amount, percentage = 0){
	var amount = parseFloat(amount);
	var percentage = isNaN(percentage) ? 0 : parseFloat(percentage);

	return (amount + ((percentage/100)*amount));
}

//Substract specified percentage to the input amount.
function __substract_percent(amount, percentage = 0){
	var amount = parseFloat(amount);
	var percentage = isNaN(percentage) ? 0 : parseFloat(percentage);

	return (amount - ((percentage/100)*amount));
}

//Returns the principle amount for the calculated amount and percentage
function __get_principle(amount, percentage = 0, minus = false){
	var amount = parseFloat(amount);
	var percentage = isNaN(percentage) ? 0 : parseFloat(percentage);

	if(minus) {
		return ((100*amount) / (100 - percentage));
	} else {
		return ((100*amount) / (100 + percentage));
	}
}

//Returns the rate at which amount is calculated from principal
function __get_rate(principal, amount){
	var principal = isNaN(principal) ? 0 : parseFloat(principal);
	var amount = isNaN(amount) ? 0 : parseFloat(amount);
	var interest = amount - principal;
	return ((interest / principal)*100);
}

function __tab_key_up(e){
	if(e.keyCode == 9){
		return true;
	}
}

function __currency_trans_from_en(input, show_symbol = true, use_page_currency = false, precision = __currency_precision){

	if(use_page_currency && __p_currency_symbol){
		var s = __p_currency_symbol;
		var thousand = __p_currency_thousand_separator;
		var decimal = __p_currency_decimal_separator;
	} else {
		var s = __currency_symbol;
		var thousand = __currency_thousand_separator;
		var decimal = __currency_decimal_separator;
	}

	symbol = '';
	var format = "%s%v";
	if(show_symbol){
		symbol = s;
		format = "%s %v";
		if(__currency_symbol_placement == 'after'){
			format = "%v %s";
		}
	}

	return accounting.formatMoney(input, symbol, precision, thousand, decimal, format);
}

function __currency_convert_recursively(element, use_page_currency = false){

	element.find('.display_currency').each(function(){

		var value = $(this).text();

		var show_symbol = $(this).data('currency_symbol');
		if(show_symbol == undefined || show_symbol != true){
			show_symbol = false;
		}

		var highlight = $(this).data('highlight');
		if(highlight == true){
			__highlight(value, $(this));
		}
		
		$(this).text( __currency_trans_from_en( value, show_symbol, use_page_currency ) );
	});
}

function __translate( str, obj = []){
	var trans = LANG[str];
	$.each( obj, function( key, value ) {
	  trans = trans.replace(":" + key , value);
	});
	if (trans) {
		return trans;
	} else {
		return str;
	}
}

//If the value is positive, text-success class will be applied else text-danger
function __highlight(value, obj) {

	obj.removeClass('text-success').removeClass('text-danger');
	if(value > 0){
        obj.addClass('text-success');
    } else if(value < 0){
        obj.addClass('text-danger');
    }
}

//Unformats the currency/number
function __number_uf(input, use_page_currency = false){

	if(use_page_currency && __currency_decimal_separator){
		var decimal = __p_currency_decimal_separator;
	} else {
		var decimal = __currency_decimal_separator;
	}

	return accounting.unformat(input, decimal);
}

//Alias of currency format, formats number
function __number_f(input, show_symbol = false, use_page_currency = false, precision = __currency_precision){
	return __currency_trans_from_en(input, show_symbol, use_page_currency, precision);
}

//Read input and convert it into natural number
function __read_number(input_element, use_page_currency = false){
	return __number_uf(input_element.val(), use_page_currency)
}

//Write input by converting to formatted number
function __write_number(input_element, value, use_page_currency = false, precision = __currency_precision){
	input_element.val(__number_f(value, false, use_page_currency, precision));
}

//Return the font-awesome html based on class value
function __fa_awesome($class = 'fa-refresh fa-spin fa-fw '){
	return '<i class="fa ' + $class + '"></i>';
}

//Converts standard dates (YYYY-MM-DD) to human readable dates
function __show_date_diff_for_human(element){
	element.find('.time-to-now').each( function(){
		var string = $(this).text();
		$(this).text( moment(string).toNow(true) );
	});

	element.find('.time-from-now').each( function(){
		var string = $(this).text();
		$(this).text( moment(string).from(moment()) );
	});
}

//Rounds a number to Iraqi dinnar
function round_to_iraqi_dinnar(value){

	//Adjsustment
    var remaining = value % 250;
    if(remaining >= 125 ){
        value += (250-remaining);
    } else {
        value -= remaining;
    }

    return value;
}

function __select2(selector){
	if($('html').attr('dir') == 'rtl')
		selector.select2({dir: "rtl"});
	else
		selector.select2();
}

function update_font_size(){
	var font_size = localStorage.getItem("upos_font_size");
	var font_size_array = [];
	font_size_array['s'] = ' - 3px';
	font_size_array['m'] = '';
	font_size_array['l'] = ' + 3px';
	font_size_array['xl'] = ' + 6px';
	if (typeof(font_size) !== "undefined") {
		$('header').css('font-size', 'calc(100% ' + font_size_array[font_size] + ')');
		$('footer').css('font-size', 'calc(100% ' + font_size_array[font_size] + ')');
		$('section').each( function(){
			if(!$(this).hasClass('print_section')){
				$(this).css('font-size', 'calc(100% ' + font_size_array[font_size] + ')');
			}
		});
		$('div.modal').css('font-size', 'calc(100% ' + font_size_array[font_size] + ')');

	}
}

function sum_table_col(table, class_name){
	var sum = 0;
	table.find('tbody').find('tr').each( function(){
		if(parseFloat($(this).find('.' + class_name).data('orig-value'))){
			sum += parseFloat($(this).find('.' + class_name).data('orig-value'));
		}
	});

	return sum;
}