//<!--



function selectAllItemsIn(sel) {

        var i = 0;

        while (i < sel.options.length) {

                if (sel.options[i].value != '') {

			//sel.options[i].selected = 1;

			document.forms.taskform.lista.value = document.forms.taskform.lista.value + "|" + sel.options[i].value;

                }

                i++;

        }

	return true; // let form to submit

}



	var form;	

	var options;

	var lista;

	var firstrun;

	var oidSep;

	

function generate() {



	form = document.forms.taskform;

	options = form.list.options;

	lista = form.list;

	firstrun = false;

	separator = ' - ';

	oidSep = ':';



		len = lista.options.length;

		if (lista.options[len-1].value == 'x') {

			len--;

			lista.length = len;

			// last spacer cell delete

				firstrun = true;

		}



		current = new Array();



		for (var i=0; i<len; i++) {



			levelArray = lista.options[i].value.split(oidSep);

			level = 0;		//not needed here

			//level = levelArray[0];



			if (!current[level]) {

				current[level] = '1';

			}

			else {

				current[level]++;

				level++;

				current[level] = '0';

			}



			j = 0;



			if (!firstrun) {			

				pufferArray = options[i].text.split(separator);

				puffer = pufferArray[1];

			} else {

				puffer = options[i].text;

			}



			options[i].text = '';

			while ((current[j]) && (current[j] != '0')) {

				options[i].text = options[i].text + current[j] + '.';

				j++;

			}

			options[i].text = options[i].text + separator + puffer; // + separator + options[i].value;

		}



firstrun=false;

}





function moveTop() {



	form = document.forms.taskform;

	options = form.list.options;

	lista = form.list;

	firstrun = false;

	separator = ' - ';

	oidSep = ':';



	selected = 0;

	endSelected = 0;

	selectedLen = 0;

	selectPufferVal = new Array();

	selectPufferText = new Array();

	selectPufferOid = new Array();



	for (var i=0; i<lista.length; i++) {

		if (options[i].selected) {

			selected = i;

			options[i].selected = false;

			i = lista.length;

		}

	}







	for (var i=0; i<options.length; i++) {

		selectPufferText[i] = options[i].text;

		selectPufferVal[i] = options[i].value;

	}



	for (var i=1; i<selected+1; i++) {

		options[i].text = selectPufferText[i-1];

		options[i].value = selectPufferVal[i-1];

	}



	lista.options[0].text = selectPufferText[selected];

	lista.options[0].value = selectPufferVal[selected];

	lista.options[0].selected = true;





}





function moveUp() {



	form = document.forms.taskform;

	options = form.list.options;

	lista = form.list;

	firstrun = false;

	separator = ' - ';

	oidSep = ':';



	selected = 0;

	endSelected = 0;

	selectedLen = 0;

	selectPufferVal = new Array();

	selectPufferText = new Array();

	selectPufferOid = new Array();



	for (var i=0; i<lista.length; i++) {

		if (options[i].selected) {

			selected = i;

			options[i].selected = false;

			i = lista.length;

		}

	}



	if (selected > 0) {

		selectPufferText[selected-1] = options[selected-1].text;

		options[selected-1].text = options[selected].text;

		options[selected].text = selectPufferText[selected-1];	



		selectPufferVal[selected-1] = options[selected-1].value;

		options[selected-1].value = options[selected].value;

		options[selected].value = selectPufferVal[selected-1];	



		options[selected-1].selected = true;

		options[selected].selected = false;

	}







}





function moveDown() {



	form = document.forms.taskform;

	options = form.list.options;

	lista = form.list;

	firstrun = false;

	separator = ' - ';

	oidSep = ':';



	selected = 0;

	endSelected = 0;

	selectedLen = 0;

	selectPufferVal = new Array();

	selectPufferText = new Array();

	selectPufferOid = new Array();



	hasSelected = false;



	for (var i=0; i<lista.length; i++) {

		if (options[i].selected) {

			selected = i;

			options[i].selected = false;

			i = lista.length;

			hasSelected = true;

		}

	}



	if ((selected < lista.length-1) && hasSelected) {

		selectPufferText[selected+1] = options[selected+1].text;

		options[selected+1].text = options[selected].text;

		options[selected].text = selectPufferText[selected+1];	



		selectPufferVal[selected+1] = options[selected+1].value;

		options[selected+1].value = options[selected].value;

		options[selected].value = selectPufferVal[selected+1];	



		options[selected+1].selected = true;

		options[selected].selected = false;



	}







}







function moveBottom() {



	form = document.forms.taskform;

	options = form.list.options;

	lista = form.list;

	firstrun = false;

	separator = ' - ';

	oidSep = ':';



	selected = 0;

	endSelected = 0;

	selectedLen = 0;

	selectPufferVal = new Array();

	selectPufferText = new Array();

	selectPufferOid = new Array();

	end = lista.length - 1;





	for (var i=0; i<lista.length; i++) {

		if (options[i].selected) {

			selected = i;

			options[i].selected = false;

			i = lista.length;

		}

	}





	for (var i=0; i<options.length; i++) {

		selectPufferText[i] = options[i].text;

		selectPufferVal[i] = options[i].value;

	}





	for (var i=selected; i<end; i++) {

		options[i].text = selectPufferText[i+1];

		options[i].value = selectPufferVal[i+1];

	}



	lista.options[end].text = selectPufferText[selected];

	lista.options[end].value = selectPufferVal[selected];

	lista.options[end].selected = true;



	

}



//-->

