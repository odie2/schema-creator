jQuery(document).ready(function($) {

//********************************************************
// catch-all resize function after form is loaded
//********************************************************

	$(window).resize(function() {
		var formHeight	= $('div#TB_window').height() * 0.9;
		var formWidth	= $('div#TB_window').width() * 0.9;

		$("#TB_ajaxContent").animate({
			height:	formHeight,
			width:	formWidth
		}, {
			duration: 100
		});

	});

//********************************************************
// reset form on media row button click or cancel
//********************************************************

	$('.schema_clear').click(function() {
		$('div#schema_builder select#schema_type').find('option:first').attr('selected', 'selected');
		$('div#schema_builder div.insert_button' ).hide();
		$('div#schema_builder div.sc_option' ).hide();
		$('div#schema_builder input#clone_actor' ).hide();
		$('div#schema_builder div#sc_messages p.pending' ).hide();
		$('div#schema_builder div#sc_messages p.start' ).show();
		$('div#schema_builder div.sc_option input' ).val();
	});

//********************************************************
// resize thickbox form based on browser size
//********************************************************

	$('div#schema_builder select#schema_type').change( function() {
//		$('div#TB_ajaxContent').addClass('schema_pop');

		var formHeight	= $('div#TB_window').height() * 0.9;
		var formWidth	= $('div#TB_window').width() * 0.9;
		
		$("#TB_ajaxContent").animate({
			height:	formHeight,
			width:	formWidth
		}, {
			duration: 800
		});
	});

//********************************************************
// change values based on schema type
//********************************************************

	$('div#schema_builder select#schema_type').change( function() {
		
		$('span.warning').remove(); // clear any warning messages from fields
		
		var type = $(this).val();
		console.log(type);
		if(type == 'none' ) {
			$('div#schema_builder div.insert_button' ).hide();
			$('div#schema_builder div.sc_option' ).hide();
			$('div#schema_builder div#sc_messages p.pending' ).hide();
			$('div#schema_builder div#sc_messages p.start' ).show();
			$('div#schema_builder div.sc_option input' ).val();
			$('div#schema_builder input#clone_actor' ).hide();
		}
		
		if(type !== 'none' ) {
			$('div#schema_builder div#sc_messages p.start' ).hide();
			$('div#schema_builder div.insert_button' ).show();
		}
		
		//person
		if(type == 'person' ) {
			$('div#sc_name').show();
			$('div#sc_orgname').show();
			$('div#sc_jobtitle').show();
			$('div#sc_url').show();
			$('div#sc_description').show();
			$('div#sc_bday').show();
			$('div#sc_street').show();
			$('div#sc_pobox').show();
			$('div#sc_city').show();
			$('div#sc_state').show();
			$('div#sc_postalcode').show();
			$('div#sc_country').show();
			$('div#sc_email').show();
			$('div#sc_phone').show();

			// not needed
			$('div#sc_fax').hide();
			$('div#sc_orgtype').hide();
			$('div#sc_brand').hide();
			$('div#sc_manfu').hide();
			$('div#sc_model').hide();
			$('div#sc_prod_id').hide();
			$('div#sc_ratings').hide();
			$('div#sc_price').hide();
			$('div#sc_condition').hide();
			$('div#sc_evtype').hide();
			$('div#sc_sdate').hide();
			$('div#sc_edate').hide();
			$('div#sc_stime').hide();
			$('div#sc_duration').hide();
			$('div#sc_director').hide();
			$('div#sc_producer').hide();
			$('div.sc_actor').hide();
			$('input#clone_actor').hide();
			$('div#sc_author').hide();
			$('div#sc_publisher').hide();
			$('div#sc_pubdate').hide();
			$('div#sc_edition').hide();
			$('div#sc_isbn').hide();
			$('div#sc_formats').hide();
			$('div#sc_rev_body').hide();
			$('div#sc_rev_name').hide();
			$('div#sc_reviews').hide();

			// message displays
			$('div#sc_messages p.start').hide();
			$('div#sc_messages p.pending').hide();
		}

		// product
		if(type == 'product' ) {
			$('div#sc_name').show();
			$('div#sc_url').show();
			$('div#sc_description').show();
			$('div#sc_brand').show();
			$('div#sc_manfu').show();
			$('div#sc_model').show();
			$('div#sc_prod_id').show();
			$('div#sc_ratings').show();
			$('div#sc_price').show();
			$('div#sc_condition').show();
			
			// not needed
			$('div#sc_orgtype').hide();
			$('div#sc_orgname').hide();
			$('div#sc_jobtitle').hide();
			$('div#sc_bday').hide();
			$('div#sc_street').hide();
			$('div#sc_pobox').hide();
			$('div#sc_city').hide();
			$('div#sc_state').hide();
			$('div#sc_postalcode').hide();
			$('div#sc_country').hide();
			$('div#sc_email').hide();
			$('div#sc_phone').hide();
			$('div#sc_fax').hide();
			$('div#sc_evtype').hide();
			$('div#sc_sdate').hide();
			$('div#sc_edate').hide();
			$('div#sc_stime').hide();
			$('div#sc_duration').hide();
			$('div#sc_director').hide();
			$('div#sc_producer').hide();
			$('div.sc_actor').hide();
			$('input#clone_actor').hide();
			$('div#sc_author').hide();
			$('div#sc_publisher').hide();
			$('div#sc_pubdate').hide();
			$('div#sc_edition').hide();
			$('div#sc_isbn').hide();
			$('div#sc_formats').hide();
			$('div#sc_rev_body').hide();
			$('div#sc_rev_name').hide();
			$('div#sc_reviews').hide();

			// message displays
			$('div#sc_messages p.start').hide();
			$('div#sc_messages p.pending').hide();

		}

		// event
		if(type == 'event' ) {
			$('div#sc_evtype').show();
			$('div#sc_name').show();
			$('div#sc_url').show();
			$('div#sc_description').show();
			$('div#sc_sdate').show();
			$('div#sc_edate').show();
			$('div#sc_stime').show();
			$('div#sc_duration').show();
			$('div#sc_street').show();
			$('div#sc_pobox').show();
			$('div#sc_city').show();
			$('div#sc_state').show();
			$('div#sc_postalcode').show();
			$('div#sc_country').show();

			// not needed
			$('div#sc_orgtype').hide();
			$('div#sc_orgname').hide();
			$('div#sc_jobtitle').hide();
			$('div#sc_bday').hide();
			$('div#sc_email').hide();
			$('div#sc_phone').hide();
			$('div#sc_fax').hide();
			$('div#sc_brand').hide();
			$('div#sc_manfu').hide();
			$('div#sc_model').hide();
			$('div#sc_prod_id').hide();
			$('div#sc_ratings').hide();
			$('div#sc_price').hide();
			$('div#sc_condition').hide();
			$('div#sc_director').hide();
			$('div#sc_producer').hide();
			$('div.sc_actor').hide();
			$('input#clone_actor').hide();
			$('div#sc_author').hide();
			$('div#sc_publisher').hide();
			$('div#sc_pubdate').hide();
			$('div#sc_edition').hide();
			$('div#sc_isbn').hide();
			$('div#sc_formats').hide();
			$('div#sc_rev_body').hide();
			$('div#sc_rev_name').hide();
			$('div#sc_reviews').hide();

			// message displays
			$('div#sc_messages p.start').hide();
			$('div#sc_messages p.pending').hide();
		}

		// organization
		if(type == 'organization' ) {
			$('div#sc_orgtype').show();
			$('div#sc_name').show();
			$('div#sc_url').show();
			$('div#sc_description').show();
			$('div#sc_street').show();
			$('div#sc_pobox').show();
			$('div#sc_city').show();
			$('div#sc_state').show();
			$('div#sc_postalcode').show();
			$('div#sc_country').show();
			$('div#sc_email').show();
			$('div#sc_phone').show();
			$('div#sc_fax').show();
			
			// not needed
			$('div#sc_orgname').hide();
			$('div#sc_jobtitle').hide();
			$('div#sc_bday').hide();
			$('div#sc_brand').hide();
			$('div#sc_manfu').hide();
			$('div#sc_model').hide();
			$('div#sc_prod_id').hide();
			$('div#sc_ratings').hide();
			$('div#sc_price').hide();
			$('div#sc_condition').hide();
			$('div#sc_evtype').hide();
			$('div#sc_sdate').hide();
			$('div#sc_edate').hide();
			$('div#sc_stime').hide();
			$('div#sc_duration').hide();
			$('div#sc_director').hide();
			$('div#sc_producer').hide();
			$('div.sc_actor').hide();
			$('input#clone_actor').hide();
			$('div#sc_author').hide();
			$('div#sc_publisher').hide();
			$('div#sc_pubdate').hide();
			$('div#sc_edition').hide();
			$('div#sc_isbn').hide();
			$('div#sc_formats').hide();
			$('div#sc_rev_body').hide();
			$('div#sc_rev_name').hide();
			$('div#sc_reviews').hide();

			// message displays
			$('div#sc_messages p.start').hide();
			$('div#sc_messages p.pending').hide();
		}

		// movie
		if(type == 'movie' ) {
			$('div#sc_name').show();
			$('div#sc_url').show();
			$('div#sc_description').show();
			$('div#sc_director').show();
			$('div#sc_producer').show();
			$('div.sc_actor').show();
			$('input#clone_actor').show();

			// not needed
			$('div#sc_street').hide();
			$('div#sc_pobox').hide();
			$('div#sc_city').hide();
			$('div#sc_state').hide();
			$('div#sc_postalcode').hide();
			$('div#sc_country').hide();
			$('div#sc_orgtype').hide();
			$('div#sc_orgname').hide();
			$('div#sc_jobtitle').hide();
			$('div#sc_bday').hide();
			$('div#sc_email').hide();
			$('div#sc_phone').hide();
			$('div#sc_fax').hide();
			$('div#sc_brand').hide();
			$('div#sc_manfu').hide();
			$('div#sc_model').hide();
			$('div#sc_prod_id').hide();
			$('div#sc_ratings').hide();
			$('div#sc_price').hide();
			$('div#sc_condition').hide();
			$('div#sc_evtype').hide();
			$('div#sc_sdate').hide();
			$('div#sc_edate').hide();
			$('div#sc_stime').hide();
			$('div#sc_duration').hide();
			$('div#sc_author').hide();
			$('div#sc_publisher').hide();
			$('div#sc_pubdate').hide();
			$('div#sc_edition').hide();
			$('div#sc_isbn').hide();
			$('div#sc_formats').hide();
			$('div#sc_rev_body').hide();
			$('div#sc_rev_name').hide();
			$('div#sc_reviews').hide();

			// message displays
			$('div#sc_messages p.start').hide();
			$('div#sc_messages p.pending').hide();
		}

		// book
		if(type == 'book' ) {

			$('div#sc_name').show();
			$('div#sc_url').show();
			$('div#sc_description').show();
			$('div#sc_author').show();
			$('div#sc_publisher').show();
			$('div#sc_pubdate').show();
			$('div#sc_edition').show();
			$('div#sc_isbn').show();
			$('div#sc_formats').show();
						
			// not needed
			$('div#sc_street').hide();
			$('div#sc_pobox').hide();
			$('div#sc_city').hide();
			$('div#sc_state').hide();
			$('div#sc_postalcode').hide();
			$('div#sc_country').hide();
			$('div#sc_orgtype').hide();
			$('div#sc_orgname').hide();
			$('div#sc_jobtitle').hide();
			$('div#sc_bday').hide();
			$('div#sc_email').hide();
			$('div#sc_phone').hide();
			$('div#sc_fax').hide();
			$('div#sc_brand').hide();
			$('div#sc_manfu').hide();
			$('div#sc_model').hide();
			$('div#sc_prod_id').hide();
			$('div#sc_ratings').hide();
			$('div#sc_price').hide();
			$('div#sc_condition').hide();
			$('div#sc_evtype').hide();
			$('div#sc_sdate').hide();
			$('div#sc_edate').hide();
			$('div#sc_stime').hide();
			$('div#sc_duration').hide();
			$('div#sc_director').hide();
			$('div#sc_producer').hide();
			$('div.sc_actor').hide();
			$('input#clone_actor').hide();
			$('div#sc_rev_body').hide();
			$('div#sc_rev_name').hide();
			$('div#sc_reviews').hide();

			// message displays
			$('div#sc_messages p.start').hide();
			$('div#sc_messages p.pending').hide();
		}
	
		// review
		if(type == 'review' ) {

			$('div#sc_name').show();
			$('div#sc_url').show();
			$('div#sc_description').show();
			$('div#sc_rev_body').show();
			$('div#sc_author').show();
			$('div#sc_rev_name').show();
			$('div#sc_pubdate').show();
			$('div#sc_reviews').show();

			// not needed
			$('div#sc_street').hide();
			$('div#sc_pobox').hide();
			$('div#sc_city').hide();
			$('div#sc_state').hide();
			$('div#sc_postalcode').hide();
			$('div#sc_country').hide();
			$('div#sc_orgtype').hide();
			$('div#sc_orgname').hide();
			$('div#sc_jobtitle').hide();
			$('div#sc_bday').hide();
			$('div#sc_email').hide();
			$('div#sc_phone').hide();
			$('div#sc_fax').hide();
			$('div#sc_brand').hide();
			$('div#sc_manfu').hide();
			$('div#sc_model').hide();
			$('div#sc_prod_id').hide();
			$('div#sc_price').hide();
			$('div#sc_condition').hide();
			$('div#sc_evtype').hide();
			$('div#sc_sdate').hide();
			$('div#sc_edate').hide();
			$('div#sc_stime').hide();
			$('div#sc_duration').hide();
			$('div#sc_director').hide();
			$('div#sc_producer').hide();
			$('div.sc_actor').hide();
			$('input#clone_actor').hide();
			$('div#sc_publisher').hide();
			$('div#sc_ratings').hide();
			
			// message displays
			$('div#sc_messages p.start').hide();
			$('div#sc_messages p.pending').hide();
		}

	});	// end schema check


//********************************************************
// jquery datepicker(s)
//********************************************************

	// get current year, futureproof that bitch
	var currentyear = new Date().getFullYear();
	
	// datepicker for birthday, offset the starting date by 15 years
	$( 'input#schema_bday' ).datepicker({
		onSelect: function( selectedDate ) {
			$('input#schema_bday').datepicker( 'option', 'maxDate', selectedDate );
		},
		dateFormat:		'mm/dd/yy',
		defaultDate:	'-15y',
		changeMonth:	true,
		changeYear:		true,
		yearRange:		'1800:' + currentyear + '',
		onClose: function() {
			$('input#schema_bday').trigger('change');
		},
		altField:		'input#schema_bday-format',
		altFormat:		'yy-mm-dd'

	}); // end datepicker for birthday

	$( 'input#schema_sdate' ).datepicker({
		dateFormat:		'mm/dd/yy',
		defaultDate:	null,
		changeMonth:	true,
		changeYear:		true,
		onClose: function() {
			$('input#schema_sdate').trigger('change');
		},
		altField:		'input#schema_sdate-format',
		altFormat:		'yy-mm-dd'

	}); // end datepicker for start date

	$( 'input#schema_edate' ).datepicker({
		dateFormat:		'mm/dd/yy',
		defaultDate:	null,
		changeMonth:	true,
		changeYear:		true,
		onClose: function() {
			$('input#schema_edate').trigger('change');
		},
		altField:		'input#schema_edate-format',
		altFormat:		'yy-mm-dd'

	}); // end datepicker for end date

	$( 'input#schema_pubdate' ).datepicker({
		dateFormat:		'mm/dd/yy',
		defaultDate:	'-1y',
		changeMonth:	true,
		changeYear:		true,
		onClose: function() {
			$('input#schema_pubdate').trigger('change');
		},
		altField:		'input#schema_pubdate-format',
		altFormat:		'yy-mm-dd'

	}); // end datepicker for publish date

	$( 'input#schema_revdate' ).datepicker({
		dateFormat:		'mm/dd/yy',
		defaultDate:	null,
		changeMonth:	true,
		changeYear:		true,
		onClose: function() {
			$('input#schema_revdate').trigger('change');
		},
		altField:		'input#schema_revdate-format',
		altFormat:		'yy-mm-dd'

	}); // end datepicker for publish date

//********************************************************
// timepicker add-on
//********************************************************

	$('input#schema_stime').timepicker({
		ampm:	true,
		hour:	12,
		minute: 30
	});

	$('input#schema_duration').timepicker({
		ampm: false
	});
	
//********************************************************
// currency formatting
//********************************************************


	$('div#schema_builder input.sc_currency').blur(function() {
		$('div#schema_builder input.sc_currency').formatCurrency({
			colorize: true,
			roundToDecimalPlace: 2,
			groupDigits: false
		});
	});


//********************************************************
// cloning for actors
//********************************************************

	function block_counter() {

		var count = 0;
		$('div.sc_actor').attr('id', function() {
			count++;
			return 'sc_actor_' + count;
		});
	}

	function label_counter() {
		
		var count = 0;
		$('div.sc_actor label').attr('for', function() {
			count++;
			return 'schema_actor_' + count;
		});
	}

	function input_counter() {
		
		var count = 0;
		$('div.sc_actor input').attr('id', function() {
			count++;
			return 'schema_actor_' + count;
		});
	}

	function names_counter() {
		
		var count = 0;
		$('div.sc_actor input').attr('name', function() {
			count++;
			return 'schema_actor_' + count;
		});
	}
	

    $('input#clone_actor').click(function(){
		$('div.sc_repeater:last').clone(true).insertAfter('div.sc_repeater:last');
		$('div.sc_repeater:last input').val('');
		block_counter();
		label_counter();
		input_counter();
		names_counter();
    });

//********************************************************
// trigger checkbox on label
//********************************************************


	$('div.sc_option label[rel="checker"]').each(function() {
		$(this).click(function() {

			var check_me = $(this).prev('input.schema_check');
			var is_check = $(check_me).is(':checked');

			if (is_check === false) {
				$(check_me).prop('checked', true);
				$(check_me).trigger('change');
			}

			if (is_check === true) {
				$(check_me).prop('checked', false);
				$(check_me).trigger('change');
			}

		});
	});

//********************************************************
// remove non-numeric characters
//********************************************************

	$('input.schema_numeric').keyup(function() {
			
			var numcheck = $.isNumeric($(this).val() );

			if(this.value.length > 0 && numcheck === false) {
				this.value = this.value.replace(/[^0-9\.]/g,'');
				$('span.warning').remove();
				$(this).parents('div.sc_option').append('<span class="warning">No non-numeric characters allowed</span>');
			}

			if(numcheck === true)
				$('span.warning').remove();

	});

//********************************************************
// remove numeric warning when other fields entered
//********************************************************

	$('div.sc_option input').not('.schema_numeric').keyup(function() {
		$('span.warning').remove();
	});

//********************************************************
// You're still here? It's over. Go home.
//********************************************************
	

});	// end schema form init
