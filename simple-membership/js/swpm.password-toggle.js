(function ($) {
    $(document).ready(function () {
        $("#swpm-password-toggle-checkbox").attr("checked",false);


        $("#swpm-password-toggle-checkbox").change(function(e){
            
            var field_state = $(this).data("state");

            if(field_state=="password-hidden")
            {   
                $(this).data("state","password-visible");                       
                $("#swpm_password").attr("type","text");
            }
            else{
                $(this).data("state","password-hidden");                               
                $("#swpm_password").attr("type","password");
            }

        });
    });
})(jQuery);