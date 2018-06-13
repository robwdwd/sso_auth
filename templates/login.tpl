{% include 'header.tpl' %}


<form action="{{urlpath}}login" method="post" class="pure-form pure-form-aligned">
    <fieldset>
        <legend>Login</legend>
        <input type="hidden" name="{{form_token_key}}" value="{{form_token}}" />
	    <input type="hidden" name="target" value="{{x_target}}" />
        <div class="pure-control-group">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" placeholder="Username">
        </div>

        <div class="pure-control-group">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" placeholder="Password">
        </div>

        {% if errors is not empty %}
        <div class="pure-control-group">
            <label for="errors"></label>
            <span class="pure-form-message-inline">
                {% for error in errors %}
                {{error|raw}}<br />
                {% endfor %}
            </span>
        </div>
        {% endif %}

        <div class="pure-controls">
            <button type="submit" class="pure-button pure-button-primary">Submit</button>
        </div>
    </fieldset>
</form>

{% include 'footer.tpl' %}
