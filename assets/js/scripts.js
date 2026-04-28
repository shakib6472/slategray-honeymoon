

jQuery(document).ready(function($){

    $('#honey-registrationForm').on('submit', function(e){
        e.preventDefault();
       $('.loader-overlay').show();
        let formData = $(this).serialize(); // URL-encoded

        $.ajax({
            url: honeymoon_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=honeymoon_register_user',
            success: function(response){
                
                if(response.success){

                    $('#userCode').val(response.data.code);
                    $('#successOverlay').show();
                    $('.loader-overlay').hide();
                    
                } else {
                    $('#serverAlert').show().addClass('error');
                    $('#serverAlert .message').text(response.data);
                     $('.loader-overlay').hide();
                    hideInfoBox();
                }
            }
        });

    });

    // Update Profile 
    // Hide notic box
    function hideInfoBox() {

        setTimeout(function() {
            $('#serverAlert').hide();
        },3000);

    }


    // 👁️ Toggle password show/hide (both fields)
    $('.toggle-pass').on('click', function(){

        let passInput = $('#password');
        let confirmInput = $('#confirm_password');

        let type = passInput.attr('type') === 'password' ? 'text' : 'password';

        passInput.attr('type', type);
        confirmInput.attr('type', type);
    });


    // 🔥 Step-by-step validation
    
    // $('#password').on('keyup', function(){

    // let pass = $(this).val();
    // let error = $('#passError');
    // let btn = $('#submitBtn');

    // error.text('').css('color','red');

    // btn.prop('disabled', true); // 🔴 default disable

    // if(pass.length < 6){
    //     error.text('Minimum 6 characters required');
    //     return;
    // }

    // if(!/[A-Z]/.test(pass)){
    //     error.text('Add at least 1 Capital letter');
    //     return;
    // }

    // if(!/[a-z]/.test(pass)){
    //     error.text('Add at least 1 small letter');
    //     return;
    // }

    // if(!/[0-9]/.test(pass)){
    //     error.text('Add at least 1 number');
    //     return;
    // }

    // if(!/[!@#$%^&*]/.test(pass)){
    //     error.text('Add at least 1 symbol');
    //     return;
    // }

    // // ✅ সব ঠিক → enable button
    // error.css('color','green').text('Strong password ✅');
    // btn.prop('disabled', false);
    // });


    // Copy Code
    // AJAX success হলে call করবা
    function showSuccessPopup(code){

        $('#userCode').val(code);
        $('#successOverlay').fadeIn();

    }


    // 📋 Copy button
    $('#copyCode').on('click', function(){

        let codeInput = document.getElementById('userCode');

        codeInput.select();
        codeInput.setSelectionRange(0, 99999);

        document.execCommand("copy");

        $(this).text('✅'); // feedback
    });

    
    // User Dashboard 
    $('.account-sidebar li').on('click', function(){

        let tab = $(this).data('tab');

        $('.account-sidebar li').removeClass('active');
        $(this).addClass('active');

        $('.tab').removeClass('active');
        $('#' + tab).addClass('active');

    });

});

