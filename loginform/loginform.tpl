	<form method="post" action="{%url%}">
	  <div class="loginform">
		<input type="hidden" name="action" value="login" />
		<input type="hidden" name="iteration" value="{%iteration%}" />
		<label for="nucleus_lf_name" accesskey="l">{%_LOGINFORM_NAME%}</label>: <input id="nucleus_lf_name" name="login" size="10" value="" class="formfield" />
		<br />
		<label for="nucleus_lf_pwd">{%_LOGINFORM_PWD%}</label>: <input id="nucleus_lf_pwd" name="password" size="10" type="password" value="" class="formfield" />
		<br />
		<input type="submit" value="{%_LOGIN%}" class="formbutton" />
		<br />
		<input type="checkbox" value="1" name="shared" id="nucleus_lf_shared" /><label for="nucleus_lf_shared">{%_LOGINFORM_SHARED%}</label>
	  </div>
	</form>
