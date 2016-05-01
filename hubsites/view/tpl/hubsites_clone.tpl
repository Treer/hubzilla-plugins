<script>
    function hubsite_post(path, params, method) {
        method = method || "post"; // Set method to post by default if not specified.

        // The rest of this code assumes you are not using a library.
        // It can be made less wordy if you use one.
        var form = document.createElement("form");
        form.setAttribute("method", method);
        form.setAttribute("action", path);
        for (var key in params) {
            if (params.hasOwnProperty(key)) {
                var hiddenField = document.createElement("input");
                        hiddenField.setAttribute("type", "hidden");
                        hiddenField.setAttribute("name", key);
                        hiddenField.setAttribute("value", params[key]);
                        form.appendChild(hiddenField);
            }
        }

        document.body.appendChild(form);
        form.submit();
        }
</script>
<h3>{{$header}}</h3>

<p class="descriptive-text">{{$desc}}</p>
<p class="descriptive-text">{{$notes}}</p>

<form action="hubsites" method="post" autocomplete="on" >
    {{include file="field_input.tpl" field=$repoURL}}
    <input type="hidden" name="action" value="{{$action1}}">
    <input type="submit" name="submit" value="{{$submit1}}" />
</form>
<BR><BR>

<form action="hubsites" method="post" autocomplete="off" id='repo-form'>
    <input type="hidden" name="action" value="{{$action2}}">
    <div class="hubsites-table">
        <table>
            <tr><td>Delete</td><td>Update</td><td>Cloned Repos</td></tr>
            {{foreach $repos as $repo}}
            <tr>
                <td>
                    <div class='squaredTwo'>
                    <input type="checkbox" id="repo-delete-{{$repo.id}}" name="repodelete[]" value="{{$repo.id}}">
                    <label for="repo-delete-{{$repo.id}}"></label>
                    </div>
                </td>
                <td>
                    <div class='squaredTwo'>
                    <input type="checkbox" id="repo-update-{{$repo.id}}" name="repoupdate[]" value="{{$repo.id}}">
                    <label for="repo-update-{{$repo.id}}"></label>
                    </div>
                </td>
                <td>
                    <div class='desc'>
                        {{$repo.url}}
                    </div>
                </td>
                <td>
                    <div class='desc'>
                        <a href="" onclick='hubsite_post("{{$baseurl}}/hubsites", {action: "clone", repoURL: "{{$repo.url}}"}); return false;'>Re-import</a>
                    </div>
                </td>
            </tr>
            {{/foreach}}
        </table>
    </div>
    <BR><BR>
    <input type="submit" name="submit" value="{{$submit2}}" />
</form>
