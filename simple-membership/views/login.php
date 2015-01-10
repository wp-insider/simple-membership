<div class="swpm-login-widget-form">
<form id="swpm-login-form" name="swpm-login-form" method="post" action="">
    <div class="forms">
	    <div>
                <div ><label for="swpm_user_name" class="eMember_label"><?= BUtils::_('User Name')?></label></div>
	    </div
	    <div>
	        <div ><input type="text" class="swpm_text_field" id="swpm_user_name"  value="" size="30" name="swpm_user_name" /></div>
	    </div>
	    <div>
	    	<div ><label for="swpm_password" class="eMember_label"><?= BUtils::_('Password')?></label></div>
		</div>
	    <div>
	        <div ><input type="password" class="swpm_text_field" id="swpm_password" value="" size="30" name="swpm_password" /></div>
	    </div>
	    <div>
	        <div ><input type="checkbox" name="rememberme" value="checked='checked'"> <?= BUtils::_('Remember Me')?></div>
	    </div>
	    <div>
	        <div >
                    <input type="submit" name="swpm-login" value="<?= BUtils::_('Login')?>"/>
	        </div>
	    </div>
        <div>
	        <div >
	        <a id="forgot_pass" href="<?= $password_reset_url;?>"><?= BUtils::_('Forgot Password')?>?</a>
	        </div>
	    </div>
	    <div>
	        <div ><a id="register" class="register_link" href="<?= $join_url; ?>"><?= BUtils::_('Join Us')?></a></div>
	    </div>
	    <div>
	    	<div ><span class="swpm-login-widget-action-msg"><?= $auth->get_message();?></span></div>
	    </div>
	</div>
</form>
</div>
