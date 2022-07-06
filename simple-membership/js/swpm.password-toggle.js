(function ($) {
    $(document).ready(function () {
         
    var password_field_width = $("#swpm_password").outerWidth();
    var eye_btn_width = $("#swpm-toggle-password-visiblity").outerWidth();
    eye_btn_width = eye_btn_width+2;    //padding right: 2px
    password_field_width = password_field_width-(28+eye_btn_width+2);    //28 for padding-right 

        $("#swpm_password").css({
            'width' : password_field_width + 'px'             
        });

        $("#swpm-toggle-password-visiblity").click(function(e){
            e.preventDefault();
            var field_state = $(this).data("state");

            if(field_state=="password-hidden")
            {   
                $(this).data("state","password-visible");                             
                $("#swpm-password-visible").show();
                $("#swpm-password-hidden").hide();    
                $("#swpm_password").attr("type","text");
            }
            else{
                $(this).data("state","password-hidden");
                $("#swpm-password-visible").hide();
                $("#swpm-password-hidden").show();
                $("#swpm_password").attr("type","password");
            }

        });
    });
})(jQuery);