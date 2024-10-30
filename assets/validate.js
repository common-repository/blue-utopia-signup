
jQuery(function ($) {
	
	var validateBlueUtopiaEmail = function(emailStr){
		var emailStr = emailStr.toLowerCase();
		var checkTLD=1; // boolean to check TLD
		var knownDomsPat=/^(com|net|org|edu|int|mil|gov|arpa|biz|aero|name|coop|info|pro|museum)$/;  // This the list of known TLDs that an e-mail address must end with.
		var emailPat=/^(.+)@(.+)$/; // user@domain regexp
		var specialChars="\\(\\)><@,;:\\\\\\\"\\.\\[\\]";  // forbidden characters
		var validChars="\[^\\s" + specialChars + "\]";
		var quotedUser="(\"[^\"]*\")";
		var ipDomainPat=/^\[(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\]$/; // joe@[123.124.233.4] is a legale-mail address. NOTE: The square brackets are required.
		var atom=validChars + '+'; // The following string represents an atom (basically a series of non-special characters.)
		var word="(" + atom + "|" + quotedUser + ")"; // The following string represents one word in the typical username. For example, in john.doe@somewhere.com, john and doe are words. Basically, a word is either an atom or quoted string.
		var userPat=new RegExp("^" + word + "(\\." + word + ")*$"); // The following pattern describes the structure of the user
		var domainPat=new RegExp("^" + atom + "(\\." + atom +")*$"); // The following pattern describes the structure of a normal symbolic domain, as opposed to ipDomainPat, shown above.
		var matchArray=emailStr.match(emailPat); // Begin with the coarse pattern to simply break up user@domain into different pieces that are easy to analyze.
		if (matchArray==null){ // Too many/few @'s or something; basically, this address doesn't even fit the general mould of a valid e-mail address.
			return false;
		}
		var user=matchArray[1];
		var domain=matchArray[2];
		for (i=0; i<user.length; i++) { // Start by checking that only basic ASCII characters are in the strings (0-127).
			if (user.charCodeAt(i)>127) {
				return false;
			}
		}
		for (i=0; i<domain.length; i++) {
			if (domain.charCodeAt(i)>127) {
				return false;
			 }
		}
		if (user.match(userPat)==null) { // See if "user" is valid 
			return false;
		}
		var IPArray=domain.match(ipDomainPat); // See if ip address is valid 
		if (IPArray!=null) {
			for (var i=1;i<=4;i++) {
				if (IPArray[i]>255) {
					return false;
				 }
			}
			return true;
		}
		var atomPat=new RegExp("^" + atom + "$");
		var domArr=domain.split(".");
		var len=domArr.length;
		for (i=0;i<len;i++) {
			if (domArr[i].search(atomPat)==-1) {
				return false;
			 }
		}
		if (checkTLD && domArr[domArr.length-1].length!=2 && domArr[domArr.length-1].search(knownDomsPat)==-1) {
			return false;
		}
		if (len<2) {
			return false;
		}
		return true;
	}

	var validateBlueUtopiaZipCode = function(zip){
  	var zipCodePattern = /^\d{5}$|^\d{5}-\d{4}$/;
		return zipCodePattern.test(zip);
	}

	$(document).ready(function(){		
		$('aside.widget_blueutopiasignup.widget').show();
		$('form.blueutopiasignupform input[type=submit]').click(function(e) {
			e.preventDefault();
			var formid = $(this).closest('form.blueutopiasignupform').attr('id');
			var form = 'form.blueutopiasignupform#'+formid;
			//var action_url = $(form).attr('data-action');
			var action_url = blueutopiasignup.url;
			
			window.valid = true;
			
			$(form+' input, '+form+' select').removeClass('required');
			$(form+' p.message').hide();
			
			$(form+' input[type=checkbox]').each(function() {
				var required = $(this).attr("data-required");
				var requiredtext = $(this).attr("data-requiredtext");
				if(!requiredtext || $.trim(requiredtext)==''){
					var requiredtext = 'You must check this field.';
				}
				var id = $(this).attr("id");
				if(required && $.trim(required)=='yes' && $(this).is(':disabled') == false){		
					if(!$(this).is(':checked')){
						window.valid = false;
						window.thisitem = $(this);
						$(this).addClass('required');
						alert(requiredtext);
						setTimeout(function() {$(window.thisitem).focus();}, 0);
						return false;
					}
				}			
			});
			
			$(form+' input, '+form+' select').each(function() {
				var required = $(this).attr("data-required");
				var requiredtext = $(this).attr("data-requiredtext");
				if(!requiredtext || $.trim(requiredtext)==''){
					var requiredtext = 'You must fill in this field.';
				}
				var id = $(this).attr("id");
				var val = $(this).val();
				if(required && $.trim(required)=='yes' && $(this).is(':disabled') == false){
					if($.trim(val)==''){
						window.valid = false;
						window.thisitem = $(this);
						$(this).addClass('required');
						alert(requiredtext);
						setTimeout(function() {$(window.thisitem).focus();}, 0);
						return false;
					} 
					else if($(this).hasClass('email') && !validateBlueUtopiaEmail(val)){
						window.valid = false;
						window.thisitem = $(this);
						$(this).addClass('required');
						alert('You must enter in a valid email address.');
						setTimeout(function() {$(window.thisitem).focus();}, 0);	
						return false;				
					} 
					else if($(this).hasClass('zip') && !validateBlueUtopiaZipCode(val)){
						window.valid = false;
						window.thisitem = $(this);
						$(this).addClass('required');
						alert('You must enter in a valid zip code.');
						setTimeout(function() {$(window.thisitem).focus();}, 0);	
						return false;							
					}
				}
			});			

			if(window.valid){
				$.ajax({
					type: "POST",
					url: action_url,
					data: $(form).serialize(),
					dataType:  'json',
					beforeSend: function (jqXHR, settings) {
						jqXHR.id = form;
					},								
					success: showBlueUtopiaSuccess,
					error: showBlueUtopiaError
				});	
			}
			
		});
	});

	var showBlueUtopiaSuccess = function(data, textStatus, jqXHR){
		var id = $.trim(jqXHR.id);
		if(id!='' && data && data.results && $.trim(data.results.message)!=''){
			var message = $.trim(data.results.message);
  		if(data.results.status){
				$(id+' p.message').html(message);
  			$(id+' p.message').show();	
				$(id+' input.text_input').attr('value','');		
				$(id+' input.text_input').blur();	
				$(id+' select.text_input').attr('value','');		
				$(id+' select.text_input').blur();					
			} else {
				showBlueUtopiaError(jqXHR,message,'Error')
			}
		}
	}
	
	var showBlueUtopiaError = function(jqXHR, textStatus, error){
		var id = $.trim(jqXHR.id);
		if(id!=''){
			$(id+' p.message').html(textStatus);
  		$(id+' p.message').show();
		}
	}	

});